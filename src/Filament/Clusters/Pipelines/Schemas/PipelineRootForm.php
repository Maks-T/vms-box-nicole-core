<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Nicole\Box\Core\DTO\Pipeline\PipelineSlotDto;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Constants\EntityType as ET;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class PipelineRootForm
{
  public static function fill(int $entityId, string $pipelineCode, string $entityType = ET::PRODUCT_VARIANT): array
  {
    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return [];
    }

    $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;

    // Ищем правила и по короткому ключу ('product_variant'), и по полному имени класса
    $rules = BindingRule::whereIn('parent_type', array_unique([$entityType, $morphClass]))
      ->where('parent_id', $entityId)
      ->with('child')
      ->get();

    $formValues = [];

    $entity = class_exists($morphClass) ? $morphClass::find($entityId) : null;
    $typeCode = $entity ? PipelineEntityResolver::resolveTypeCode($entity) : 'general';

    $treeService = app(PipelineTreeService::class);
    $pipelineSlots = $treeService->getPipelineSlots($pipelineCode);
    /** @var array<string, PipelineSlotDto> $slots */
    $slots = $pipelineSlots[$typeCode] ?? [];

    foreach ($slots as $roleCode => $slot) {
      $slotRules = $rules->where('role', $roleCode);
      $isScalar = empty($slot->targetCode) || $slot->targetType === ET::SCALAR;

      if ($isScalar) {
        $firstRule = $slotRules->first();
        $staticMeta = $firstRule?->static_meta ?? [];
        $formValues[$roleCode] = is_array($staticMeta) ? ($staticMeta[$roleCode] ?? head($staticMeta)) : $staticMeta;
      } else {
        if ($slot->isMultiple) {
          $formValues[$roleCode] = $slotRules
            ->map(fn($rule) => $rule->child_id)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        } else {
          $formValues[$roleCode] = $slotRules->first()?->child_id;
        }
      }
    }

    return $formValues;
  }

  public static function configure(Schema $schema, string $pipelineCode, ?int $entityId = null, string $entityType = ET::PRODUCT_VARIANT): Schema
  {
    $typeCode = 'general';
    if ($entityId) {
      $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
      $entity = class_exists($morphClass) ? $morphClass::find($entityId) : null;
      if ($entity) {
        $typeCode = PipelineEntityResolver::resolveTypeCode($entity);
      }
    }

    $treeService = app(PipelineTreeService::class);
    $pipelineSlots = $treeService->getPipelineSlots($pipelineCode);
    /** @var array<string, PipelineSlotDto> $slots */
    $slots = $pipelineSlots[$typeCode] ?? [];

    $formFields = [];

    foreach ($slots as $roleCode => $slot) {
      $isScalar = empty($slot->targetCode) || $slot->targetType === ET::SCALAR;
      $displayLabel = $slot->labelKey;
      $displayTitle = is_array($displayLabel) ? ($displayLabel[app()->getLocale()] ?? head($displayLabel)) : $displayLabel;

      if ($isScalar) {
        $formFields[] = TextInput::make($roleCode)
          ->label((string)$displayTitle)
          ->numeric()
          ->required($slot->isRequired);
      } else {
        // Динамический селектор под целевой тип сущности слота (Товар, Вариант, Справочник)
        $formFields[] = PipelineEntityResolver::resolveSelectComponent(
          entityType: $slot->targetType === ET::PRODUCT_TYPE ? ET::PRODUCT_VARIANT : $slot->targetType,
          fieldName: $roleCode,
          filterTypeCode: $slot->targetCode,
          multiple: $slot->isMultiple
        )
          ->label((string)$displayTitle)
          ->required($slot->isRequired);
      }
    }

    return $schema->components([
      Grid::make(2)->schema($formFields)
    ]);
  }

  public static function save(array $data, int $entityId, string $pipelineCode, string $entityType = ET::PRODUCT_VARIANT): void
  {
    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return;
    }

    $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
    $entity = class_exists($morphClass) ? $morphClass::find($entityId) : null;
    $typeCode = $entity ? PipelineEntityResolver::resolveTypeCode($entity) : 'general';

    // Получаем канонический morph-ключ родителя
    $parentMorphType = class_exists($morphClass) ? (new $morphClass())->getMorphClass() : $entityType;

    $treeService = app(PipelineTreeService::class);
    $pipelineSlots = $treeService->getPipelineSlots($pipelineCode);
    /** @var array<string, PipelineSlotDto> $slots */
    $slots = $pipelineSlots[$typeCode] ?? [];

    DB::transaction(function () use ($data, $entityId, $morphClass, $parentMorphType, $pipeline, $slots) {
      foreach ($slots as $roleCode => $slot) {
        $inputValue = $data[$roleCode] ?? null;
        $isScalar = empty($slot->targetCode) || $slot->targetType === ET::SCALAR;
        $displayLabel = $slot->labelKey;
        $displayTitle = is_array($displayLabel) ? ($displayLabel[app()->getLocale()] ?? head($displayLabel)) : $displayLabel;

        if ($slot->isMultiple) {
          $submittedIds = is_array($inputValue) ? array_values(array_filter(array_map('intval', $inputValue))) : [];
          $childType = $slot->targetType === ET::PRODUCT_TYPE ? ET::PRODUCT_VARIANT : $slot->targetType;
          $childClass = Relation::getMorphedModel($childType);
          $childMorphType = ($childClass && class_exists($childClass)) ? (new $childClass())->getMorphClass() : $childType;

          // Удаляем старые отвязанные связи
          BindingRule::whereIn('parent_type', array_unique([$morphClass, $parentMorphType]))
            ->where('parent_id', $entityId)
            ->where('role', $roleCode)
            ->whereNotIn('child_id', $submittedIds)
            ->delete();

          // Сохраняем новые привязанные элементы
          foreach ($submittedIds as $childId) {
            BindingRule::updateOrCreate([
              'parent_type' => $parentMorphType,
              'parent_id' => $entityId,
              'role' => $roleCode,
              'child_type' => $childMorphType,
              'child_id' => $childId,
            ], [
              'pipeline_id' => $pipeline->id,
              'external_code' => 'rule_' . md5($pipeline->id . $entityId . $childId . $roleCode),
              'name' => __('Link') . ' ' . $displayTitle,
              'is_required' => $slot->isRequired,
            ]);
          }
        } else {
          if ($isScalar) {
            if ($inputValue === null || $inputValue === '') {
              BindingRule::whereIn('parent_type', array_unique([$morphClass, $parentMorphType]))
                ->where('parent_id', $entityId)
                ->where('role', $roleCode)
                ->delete();
            } else {
              BindingRule::updateOrCreate([
                'parent_type' => $parentMorphType,
                'parent_id' => $entityId,
                'role' => $roleCode,
              ], [
                'pipeline_id' => $pipeline->id,
                'external_code' => 'rule_' . md5($entityId . $inputValue . $roleCode),
                'name' => __('Parameter') . ' ' . $displayTitle,
                'child_type' => null,
                'child_id' => null,
                'static_meta' => [$roleCode => (string)$inputValue],
                'is_required' => $slot->isRequired,
              ]);
            }
          } else {
            if (empty($inputValue)) {
              BindingRule::whereIn('parent_type', array_unique([$morphClass, $parentMorphType]))
                ->where('parent_id', $entityId)
                ->where('role', $roleCode)
                ->delete();
            } else {
              $childId = (int)$inputValue;
              $childType = $slot->targetType === ET::PRODUCT_TYPE ? ET::PRODUCT_VARIANT : $slot->targetType;
              $childClass = Relation::getMorphedModel($childType);
              $childMorphType = ($childClass && class_exists($childClass)) ? (new $childClass())->getMorphClass() : $childType;

              BindingRule::updateOrCreate([
                'parent_type' => $parentMorphType,
                'parent_id' => $entityId,
                'role' => $roleCode,
              ], [
                'pipeline_id' => $pipeline->id,
                'external_code' => 'rule_' . md5($pipeline->id . $entityId . $childId . $roleCode),
                'name' => __('Link') . ' ' . $displayTitle,
                'child_type' => $childMorphType,
                'child_id' => $childId,
                'is_required' => $slot->isRequired,
              ]);
            }
          }
        }
      }
    });

    Notification::make()->title(__('Configuration saved successfully'))->success()->send();
  }
}
