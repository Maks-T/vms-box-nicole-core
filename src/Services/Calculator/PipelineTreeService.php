<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nicole\Box\Core\DTO\Pipeline\EntityReferenceDto;
use Nicole\Box\Core\DTO\Pipeline\PipelineSlotDto;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Support\Constants\CacheKey;
use Nicole\Box\Core\Support\Constants\EntityType as ET;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

/**
 * Сервис построения, валидации и анализа графа связей пайплайнов (BOM / Дерево зависимостей).
 */
class PipelineTreeService
{
  /**
   * Локальный реестр схем для тестирования и переопределения в рантайме.
   * @var array<string, array>
   */
  protected static array $schemas = [];

  public static function registerSchema(string $pipelineCode, array $schema): void
  {
    self::$schemas[$pipelineCode] = $schema;
  }

  /**
   * Получить типизированную карту слотов пайплайна в виде DTO.
   *
   * @return array<string, array<string, PipelineSlotDto>>
   */
  public function getPipelineSlots(string $pipelineCode, ?Pipeline $pipeline = null): array
  {
    if (isset(self::$schemas[$pipelineCode])) {
      $rawSchema = self::$schemas[$pipelineCode];
    } else {
      $locale = app()->getLocale();
      $rawSchema = $pipeline
        ? $pipeline->localized_schema
        : cache()->remember(CacheKey::PIPELINE_SCHEMA_PREFIX . "{$pipelineCode}_{$locale}", 3600, function () use ($pipelineCode) {
          return Pipeline::where('code', $pipelineCode)->first()?->localized_schema ?? [];
        });
    }

    if (empty($rawSchema) || !is_array($rawSchema)) {
      return [];
    }

    $slotsMap = [];

    foreach ($rawSchema as $parentType => $slots) {
      if (!is_array($slots)) {
        continue;
      }

      foreach ($slots as $key => $slotData) {
        if (!is_array($slotData)) {
          continue;
        }

        $roleCode = (string)($slotData['role_code'] ?? (is_string($key) ? $key : ''));
        if ($roleCode === '') {
          continue;
        }

        $slotsMap[$parentType][$roleCode] = PipelineSlotDto::fromArray($slotData, $roleCode);
      }
    }

    return $slotsMap;
  }

  /**
   * Получить схему пайплайна в виде массива для API-ресурсов и документации.
   *
   * @return array<string, array<string, array{label_key: string, target_type: string, target_code: string|null, is_required: bool, is_multiple: bool}>>
   */
  public function getPipelineSchema(string $pipelineCode, ?Pipeline $pipeline = null): array
  {
    $slotsMap = $this->getPipelineSlots($pipelineCode, $pipeline);
    $schemaArray = [];

    foreach ($slotsMap as $parentType => $roles) {
      foreach ($roles as $roleCode => $slotDto) {
        $schemaArray[$parentType][$roleCode] = $slotDto->toArray();
      }
    }

    return $schemaArray;
  }

  /**
   * Универсальный полиморфный анализ графа связей для корневой сущности.
   */
  public function analyzeTree(
    Model|int $rootEntity,
    string    $pipelineCode,
    string    $entityType = ET::PRODUCT_VARIANT
  ): ?array
  {
    if ($rootEntity instanceof Model) {
      $entity = $rootEntity;
    } else {
      $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
      $entity = class_exists($morphClass) ? $morphClass::find($rootEntity) : null;
    }

    if (!$entity) {
      return null;
    }

    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return null;
    }

    return $this->analyzeNode($entity, $pipeline);
  }

  /**
   * Извлечение метаданных полиморфной сущности через EntityReferenceDto.
   *
   * @return array{type: string, id: int, parent_id: int|null, name: string, slug: string|null, image_url: string|null}|null
   */
  protected function extractEntityMeta(?Model $entity): ?array
  {
    return EntityReferenceDto::fromModel($entity)?->toArray();
  }

  /**
   * Определение системного типа родителя для сопоставления со слотами схемы.
   */
  protected function resolveTypeCode(Model $entity): string
  {
    return PipelineEntityResolver::resolveTypeCode($entity);
  }

  /**
   * Рекурсивный полиморфный обход узла дерева с использованием PipelineSlotDto.
   */
  private function analyzeNode(Model $entity, Pipeline $pipeline, array $visited = []): array
  {
    $meta = $this->extractEntityMeta($entity);
    $visitKey = "{$meta['type']}_{$meta['id']}";

    if (in_array($visitKey, $visited, true)) {
      return [
        'type' => $meta['type'],
        'id' => $meta['id'],
        'parent_id' => $meta['parent_id'],
        'name' => $meta['name'] . ' (' . __('Cycle Detected') . ')',
        'slug' => $meta['slug'],
        'image_url' => $meta['image_url'],
        'is_valid' => false,
        'fields' => [],
      ];
    }

    $visited[] = $visitKey;

    $pipelineCode = $pipeline->code ?? 'default';
    $pipelineSlots = $this->getPipelineSlots($pipelineCode, $pipeline);

    $parentTypeCode = $this->resolveTypeCode($entity);
    /** @var array<string, PipelineSlotDto> $slots */
    $slots = $pipelineSlots[$parentTypeCode] ?? [];

    $isNodeValid = true;
    $fieldReports = [];

    foreach ($slots as $roleCode => $slot) {
      $isMultiple = $slot->isMultiple;

      // Полиморфный запрос правил привязки
      $rules = BindingRule::where('parent_type', $entity->getMorphClass())
        ->where('parent_id', $entity->getKey())
        ->where('role', $roleCode)
        ->orderBy('sort_order')
        ->get();

      if ($isMultiple) {
        $children = [];
        foreach ($rules as $rule) {
          $child = $rule->child;
          $isFilled = !is_null($child) || !empty($rule->static_meta);

          $childrenTrees = [];
          if ($isFilled && $child instanceof Model) {
            $childrenTrees = $this->analyzeNode($child, $pipeline, $visited);
          }

          $childData = $this->extractEntityMeta($child);

          $children[] = [
            'rule_id' => $rule->id,
            'field_code' => $roleCode,
            'label' => $rule->name ?: $slot->labelKey,
            'is_required' => false,
            'is_filled' => $isFilled,
            'is_valid' => $childrenTrees['is_valid'] ?? true,

            'type' => $childData['type'] ?? null,
            'id' => $childData['id'] ?? null,
            'parent_id' => $childData['parent_id'] ?? null,
            'name' => $childData['name'] ?? '',
            'slug' => $childData['slug'] ?? null,
            'image_url' => $childData['image_url'] ?? null,

            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'fields' => $childrenTrees['fields'] ?? [],
          ];
        }

        $isGroupFilled = count($children) > 0;
        if ($slot->isRequired && !$isGroupFilled) {
          $isNodeValid = false;
        }

        $fieldReports[] = [
          'is_multiple' => true,
          'type' => 'multiselect',
          'field_code' => $roleCode,
          'label' => $slot->labelKey,
          'is_required' => $slot->isRequired,
          'is_filled' => $isGroupFilled,
          'is_valid' => !$slot->isRequired || $isGroupFilled,
          'children' => $children,
          'virtual_meta' => [
            'parent_id' => $entity->getKey(),
            'parent_type' => $entity->getMorphClass(),
            'role' => $roleCode,
            'pipeline_id' => $pipeline->id,
            'target_type' => $slot->targetType,
            'target_code' => $slot->targetCode,
          ]
        ];

      } else {
        $rule = $rules->first();
        $isFilled = $rule && (!is_null($rule->child) || !empty($rule->static_meta));
        $isScalar = $rule && empty($rule->child_type);

        if ($slot->isRequired && !$isFilled) {
          $isNodeValid = false;
        }

        if ($rule) {
          $child = $rule->child;
          $childrenTrees = null;

          if ($isFilled && $child instanceof Model) {
            $childrenTrees = $this->analyzeNode($child, $pipeline, $visited);
            $childrenTrees['rule_id'] = $rule->id;

            if (isset($childrenTrees['is_valid']) && !$childrenTrees['is_valid']) {
              $isNodeValid = false;
            }
          }

          $childData = $child ? $this->extractEntityMeta($child) : null;
          $value = !empty($rule->static_meta) ? head($rule->static_meta) : null;

          $fieldReports[] = [
            'rule_id' => $rule->id,
            'field_code' => $roleCode,
            'label' => $rule->name ?: $slot->labelKey,
            'is_required' => $slot->isRequired,
            'is_filled' => $isFilled,
            'is_valid' => !$slot->isRequired || $isFilled,
            'value' => $value,
            'child' => $childData,
            'static_meta' => $rule->static_meta,
            'children' => ($childrenTrees && !$isScalar) ? [$childrenTrees] : [],
          ];
        } else {
          $fieldReports[] = [
            'rule_id' => null,
            'field_code' => $roleCode,
            'label' => $slot->labelKey,
            'is_required' => $slot->isRequired,
            'is_filled' => false,
            'is_valid' => !$slot->isRequired,
            'value' => null,
            'child' => null,
            'static_meta' => null,
            'children' => [],
            'virtual_meta' => [
              'parent_id' => $entity->getKey(),
              'parent_type' => $entity->getMorphClass(),
              'role' => $roleCode,
              'pipeline_id' => $pipeline->id,
              'target_type' => $slot->targetType,
              'target_code' => $slot->targetCode,
            ]
          ];
        }
      }
    }

    return [
      'type' => $meta['type'],
      'id' => $meta['id'],
      'parent_id' => $meta['parent_id'],
      'name' => $meta['name'],
      'slug' => $meta['slug'],
      'image_url' => $meta['image_url'],
      'is_valid' => $isNodeValid,
      'fields' => $fieldReports,
      'pipeline_industry' => $pipeline->industry ?? null,
    ];
  }

  /**
   * Преобразует дерево связей в компактную карту bindings (EntityReference).
   *
   * @param array $node Узел, возвращенный analyzeNode / analyzeTree
   * @return array<string, mixed>
   */
  public function extractBindings(array $node): array
  {
    $bindings = [];

    foreach ($node['fields'] ?? [] as $field) {
      $role = $field['field_code'] ?? null;
      if (!$role) continue;

      $isMultiple = !empty($field['is_multiple']);

      if ($isMultiple) {
        $items = [];
        foreach ($field['children'] ?? [] as $childNode) {
          $child = $childNode['child'] ?? null;
          if (!$child || empty($child['id'])) continue;

          $itemData = [
            'type' => (string)$child['type'],
            'id' => (int)$child['id'],
            'parent_id' => $child['parent_id'] !== null ? (int)$child['parent_id'] : null,
          ];

          if (!empty($childNode['fields'])) {
            $nested = $this->extractBindings($childNode);
            if (!empty($nested)) {
              $itemData = array_merge($itemData, $nested);
            }
          }

          $items[] = $itemData;
        }

        if (!empty($items)) {
          $bindings[$role] = $items;
        }
      } else {
        $child = $field['child'] ?? null;
        $staticMeta = $field['static_meta'] ?? null;

        if ($child && !empty($child['id'])) {
          $bindingData = [
            'type' => (string)$child['type'],
            'id' => (int)$child['id'],
            'parent_id' => $child['parent_id'] !== null ? (int)$child['parent_id'] : null,
          ];

          $childNode = $field['children'][0] ?? null;
          if ($childNode && !empty($childNode['fields'])) {
            $nested = $this->extractBindings($childNode);
            if (!empty($nested)) {
              $bindingData = array_merge($bindingData, $nested);
            }
          }

          $bindings[$role] = $bindingData;
        } elseif (!empty($staticMeta)) {
          $bindings['params'][$role] = is_array($staticMeta) ? head($staticMeta) : $staticMeta;
        }
      }
    }

    return $bindings;
  }

  /**
   * Каскадное переключение активности элементов дерева.
   */
  public function toggleTreeActiveStatus(array $node, bool $status): void
  {
    $entityId = $node['id'] ?? null;
    $entityType = $node['type'] ?? ET::PRODUCT_VARIANT;

    if ($entityId) {
      $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
      if (class_exists($morphClass)) {
        $entity = $morphClass::find($entityId);
        if ($entity && in_array('is_active', $entity->getFillable(), true)) {
          $entity->update(['is_active' => $status]);
        }
      }
    }

    $fields = $node['fields'] ?? ($node['children'] ?? []);

    foreach ($fields as $field) {
      $children = $field['children'] ?? [];
      foreach ($children as $childNode) {
        $this->toggleTreeActiveStatus($childNode, $status);
      }
    }
  }

  /**
   * Определяет код корневого типа товара для пайплайна с использованием PipelineSlotDto.
   */
  public function resolveRootTypeCode(Pipeline $pipeline): ?string
  {
    $slotsMap = $this->getPipelineSlots($pipeline->code, $pipeline);

    if (empty($slotsMap)) {
      return null;
    }

    $allParents = array_keys($slotsMap);
    $allChildren = [];

    foreach ($slotsMap as $roles) {
      foreach ($roles as $slotDto) {
        if (!empty($slotDto->targetCode)) {
          $allChildren[] = $slotDto->targetCode;
        }
      }
    }

    $rootTypes = array_values(array_diff($allParents, array_unique($allChildren)));

    return $rootTypes[0] ?? array_key_first($slotsMap);
  }
}
