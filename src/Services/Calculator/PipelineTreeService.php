<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Calculator;

use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Support\Constants\CacheKey;

class PipelineTreeService
{
  protected static array $schemas = [];

  public static function registerSchema(string $pipelineCode, array $schema): void
  {
    self::$schemas[$pipelineCode] = $schema;
  }

  public function getPipelineSchema(string $pipelineCode, ?Pipeline $pipeline = null): array
  {
    if (isset(self::$schemas[$pipelineCode])) {
      return self::$schemas[$pipelineCode];
    }

    $rawSchema = $pipeline
      ? ($pipeline->schema ?? [])
      : cache()->remember(CacheKey::PIPELINE_SCHEMA_PREFIX . $pipelineCode, 3600, function () use ($pipelineCode) {
        return Pipeline::where('code', $pipelineCode)->value('schema') ?? [];
      });

    if (empty($rawSchema) || !is_array($rawSchema) || !isset($rawSchema[collect($rawSchema)->keys()->first()][0])) {
      return $rawSchema;
    }

    $associativeSchema = [];
    foreach ($rawSchema as $parentType => $slots) {
      if (!is_array($slots)) {
        continue;
      }

      foreach ($slots as $slot) {
        $roleCode = $slot['role_code'] ?? null;
        if (!$roleCode) {
          continue;
        }

        $associativeSchema[$parentType][$roleCode] = [
          'label_key' => $slot['label_key'] ?? '',
          'type_code' => $slot['type_code'] ?? '',
          'is_required' => (bool)($slot['is_required'] ?? false),
          'is_multiple' => (bool)($slot['is_multiple'] ?? false),
        ];
      }
    }

    return $associativeSchema;
  }

  public function analyzeTree(int $rootVariantId, string $pipelineCode): ?array
  {
    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return null;
    }

    $rootVariant = ProductVariant::with('product.media')->find($rootVariantId);
    if (!$rootVariant) {
      return null;
    }

    return $this->analyzeNode($rootVariant, $pipeline);
  }

  private function analyzeNode(ProductVariant $variant, Pipeline $pipeline, array $visited = []): array
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

    $visited[] = $variant->id;

    $pipelineCode = $pipeline->code ?? 'default';
    $pipelineSchema = $this->getPipelineSchema($pipelineCode, $pipeline);

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
        $children = [];
        foreach ($rules as $rule) {
          $child = $rule->child;
          $isFilled = !is_null($child) || !empty($rule->static_meta);

          $childrenTrees = [];
          if ($isFilled && $rule->child_type === (new ProductVariant())->getMorphClass()) {
            $childrenTrees = $this->analyzeNode($child, $pipeline, $visited);
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
            'is_valid' => $childrenTrees['is_valid'] ?? true,
            'variant_id' => $childData['id'] ?? '',
            'variant_name' => $childData['name'] ?? '',
            'product_slug' => $childData['slug'] ?? null,
            'image_url' => $childData['image_url'] ?? null,
            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'fields' => $childrenTrees['fields'] ?? [],
          ];
        }

        $isGroupFilled = count($children) > 0;
        if ($slotMeta['is_required'] && !$isGroupFilled) {
          $isNodeValid = false;
        }

        $fieldReports[] = [
          'is_multiple' => true,
          'type' => 'multiselect',
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
            'pipeline_id' => $pipeline->id,
            'type_code' => $slotMeta['type_code'],
          ]
        ];

      } else {
        $rule = $rules->first();
        $isFilled = $rule && (!is_null($rule->child) || !empty($rule->static_meta));
        $isScalar = $rule && empty($rule->child_type);

        if ($slotMeta['is_required'] && !$isFilled) {
          $isNodeValid = false;
        }

        if ($rule) {
          $child = $rule->child;
          $childrenTrees = null;

          if ($isFilled && $rule->child_type === (new ProductVariant())->getMorphClass()) {
            $childrenTrees = $this->analyzeNode($child, $pipeline, $visited);

            $childrenTrees['rule_id'] = $rule->id;

            if (isset($childrenTrees['is_valid']) && !$childrenTrees['is_valid']) {
              $isNodeValid = false;
            }
          }

          $childData = null;
          $value = null;

          if ($child) {
            $childData = [
              'id' => $child->id,
              'name' => $child->getTranslation('name', $currentLocale) ?: $child->name,
              'slug' => $child->product?->slug ?? $child->slug,
              'image_url' => $child->getPreviewUrl() ?: $child->product?->getPreviewUrl(),
            ];
          } elseif (!empty($rule->static_meta)) {
            $value = head($rule->static_meta);
            $childData = [
              'id' => '',
              'name' => $value,
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
            'value' => $value,
            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'children' => ($childrenTrees && !$isScalar) ? [$childrenTrees] : [],
          ];
        } else {
          $fieldReports[] = [
            'rule_id' => null,
            'field_code' => $roleCode,
            'label' => $slotMeta['label_key'],
            'is_required' => (bool)$slotMeta['is_required'],
            'is_filled' => false,
            'is_valid' => !$slotMeta['is_required'],
            'value' => null,
            'child' => null,
            'static_meta' => null,
            'children' => [],
            'virtual_meta' => [
              'parent_id' => $variant->id,
              'parent_type' => $variant->getMorphClass(),
              'role' => $roleCode,
              'pipeline_id' => $pipeline->id,
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
      'pipeline_industry' => $pipeline->industry,
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
