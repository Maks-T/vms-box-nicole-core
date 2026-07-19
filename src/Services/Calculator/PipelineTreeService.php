<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Calculator;

use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Product;

class PipelineTreeService
{
  protected static array $schemas = [];

  public static function registerSchema(string $pipelineCode, array $schema): void
  {
    self::$schemas[$pipelineCode] = $schema;
  }

  /**
   * метод получения зарегистрированной схемы пайплайна
   */
  public function getPipelineSchema(string $pipelineCode): array
  {
    return self::$schemas[$pipelineCode] ?? [];
  }

  public function analyzeTree(int $rootVariantId, string $pipelineCode): ?array
  {
    $pipeline = Pipeline::where('external_code', $pipelineCode)->first();
    if (!$pipeline) {
      return null;
    }

    $rootVariant = ProductVariant::with('product.media')->find($rootVariantId);
    if (!$rootVariant) {
      return null;
    }

    return $this->analyzeNode($rootVariant, $pipeline->id);
  }

  /**
   * Рекурсивный обход узлов с защитой от бесконечных циклов в БД
   */
  private function analyzeNode(ProductVariant $variant, int $pipelineId, array $visited = []): array
  {
    $currentLocale = app()->getLocale();

    if (in_array($variant->id, $visited, true)) {
      return [
        'variant_id' => $variant->id,
        'variant_name' => ($variant->getTranslation('name', $currentLocale) ?: $variant->name) . ' (' . __('Cycle Detected') . ')',
        'image_url' => $variant->getPreviewUrl() ?: $variant->product?->getPreviewUrl(),
        'is_valid' => false,
        'fields' => [],
        'product_slug' => $variant->product?->slug,
      ];
    }

    // Помечаем текущую ноду как пройденную
    $visited[] = $variant->id;

    $pipelineCode = Pipeline::where('id', $pipelineId)->value('external_code') ?? 'default';
    $pipelineSchema = self::$schemas[$pipelineCode] ?? [];

    // Динамически определяем код типа текущего продукта (например: pillar, baluster, rail)
    $parentTypeCode = $variant->product?->type?->code ?? 'general';
    $schema = $pipelineSchema[$parentTypeCode] ?? [];

    $isNodeValid = true;
    $fieldReports = [];

    foreach ($schema as $roleCode => $slotMeta) {
      $isMultiple = !empty($slotMeta['is_multiple']);

      $rules = BindingRule::where('parent_type', $variant->getMorphClass())
        ->where('parent_id', $variant->id)
        ->where('role', $roleCode)
        ->orderBy('sort_order')
        ->get();

      if ($isMultiple) {
        // Обработка множественного слота (Группировка в папку)
        $children = [];
        foreach ($rules as $rule) {
          $child = $rule->child;
          $isFilled = !is_null($child) || !empty($rule->static_meta);

          $childrenTrees = [];
          // Передаем массив посещенных узлов дальше по цепочке рекурсии
          if ($isFilled && $rule->child_type === (new ProductVariant())->getMorphClass()) {
            $childrenTrees = $this->analyzeNode($child, $pipelineId, $visited);
          }

          $childData = null;
          if ($child) {
            $childData = [
              'id' => $child->id,
              'name' => $child->getTranslation('name', $currentLocale) ?: $child->name,
              'slug' => $child->product?->slug ?? $child->slug,
              'image_url' => $child->getPreviewUrl() ?: $child->product?->getPreviewUrl(),
            ];
          }

          $children[] = [
            'rule_id' => $rule->id,
            'field_code' => $roleCode,
            'label' => $rule->name ?: $slotMeta['label_key'],
            'is_required' => false,
            'is_filled' => $isFilled,
            'is_valid' => true,
            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'children' => $childrenTrees['fields'] ?? [],
          ];
        }

        $isGroupFilled = count($children) > 0;
        if ($slotMeta['is_required'] && !$isGroupFilled) {
          $isNodeValid = false;
        }

        $fieldReports[] = [
          'is_multiple' => true,
          'field_code' => $roleCode,
          'label' => $slotMeta['label_key'],
          'is_required' => (bool)$slotMeta['is_required'],
          'is_filled' => $isGroupFilled,
          'is_valid' => !$slotMeta['is_required'] || $isGroupFilled,
          'children' => $children,
          'virtual_meta' => [
            'parent_id' => $variant->id,
            'parent_type' => $variant->getMorphClass(),
            'role' => $roleCode,
            'pipeline_id' => $pipelineId,
            'type_code' => $slotMeta['type_code'],
          ]
        ];

      } else {
        // Обработка одиночного слота
        $rule = $rules->first();
        $isFilled = $rule && (!is_null($rule->child) || !empty($rule->static_meta));

        if ($slotMeta['is_required'] && !$isFilled) {
          $isNodeValid = false;
        }

        if ($rule) {
          $child = $rule->child;
          $childrenTrees = [];
          // Передаем массив посещенных узлов дальше по цепочке рекурсии
          if ($isFilled && $rule->child_type === (new ProductVariant())->getMorphClass()) {
            $childrenTrees = $this->analyzeNode($child, $pipelineId, $visited);
            if (isset($childrenTrees['is_valid']) && !$childrenTrees['is_valid']) {
              $isNodeValid = false;
            }
          }

          $childData = null;
          if ($child) {
            $childData = [
              'id' => $child->id,
              'name' => $child->getTranslation('name', $currentLocale) ?: $child->name,
              'slug' => $child->product?->slug ?? $child->slug,
              'image_url' => $child->getPreviewUrl() ?: $child->product?->getPreviewUrl(),
            ];
          } elseif (!empty($rule->static_meta)) {
            $childData = [
              'id' => '',
              'name' => head($rule->static_meta),
              'slug' => null,
              'image_url' => null,
            ];
          }

          $fieldReports[] = [
            'rule_id' => $rule->id,
            'field_code' => $roleCode,
            'label' => $rule->name ?: $slotMeta['label_key'],
            'is_required' => (bool)$slotMeta['is_required'],
            'is_filled' => $isFilled,
            'is_valid' => !$slotMeta['is_required'] || $isFilled,
            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'children' => $childrenTrees['fields'] ?? [],
          ];
        } else {
          $fieldReports[] = [
            'rule_id' => null,
            'field_code' => $roleCode,
            'label' => $slotMeta['label_key'],
            'is_required' => (bool)$slotMeta['is_required'],
            'is_filled' => false,
            'is_valid' => !$slotMeta['is_required'],
            'child' => null,
            'static_meta' => null,
            'children' => [],
            'virtual_meta' => [
              'parent_id' => $variant->id,
              'parent_type' => $variant->getMorphClass(),
              'role' => $roleCode,
              'pipeline_id' => $pipelineId,
              'type_code' => $slotMeta['type_code'],
            ]
          ];
        }
      }
    }

    return [
      'variant_id' => $variant->id,
      'variant_name' => $variant->getTranslation('name', $currentLocale) ?: $variant->name,
      'image_url' => $variant->getPreviewUrl() ?: $variant->product?->getPreviewUrl(),
      'is_valid' => $isNodeValid,
      'fields' => $fieldReports,
      'product_slug' => $variant->product?->slug,
    ];
  }

  public function toggleTreeActiveStatus(array $node, bool $status): void
  {
    $variantId = $node['variant_id'] ?? ($node['child']['id'] ?? null);

    if ($variantId) {
      ProductVariant::where('id', $variantId)->update(['is_active' => $status]);
    }

    $fields = $node['fields'] ?? ($node['children'] ?? []);

    foreach ($fields as $field) {
      $children = $field['children'] ?? [];
      foreach ($children as $childNode) {
        $this->toggleTreeActiveStatus($childNode, $status);
      }
    }
  }
}
