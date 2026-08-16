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
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class PipelineRootForm
{
  public static function fill(int $entityId, string $pipelineCode, string $entityType = 'product_variant'): array
  {
    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return [];
    }

    $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
    $rules = BindingRule::where('parent_type', $morphClass)
      ->where('parent_id', $entityId)
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
      $isScalar = empty($slot->typeCode) || $slot->typeCode === 'general';

      if ($isScalar) {
        $formValues[$roleCode] = !empty($slotRules->first()?->static_meta)
          ? head($slotRules->first()->static_meta)
          : null;
      } else {
        $activeSlotRules = $slotRules->where('pipeline_id', $pipeline->id);
        if ($slot->isMultiple) {
          $formValues[$roleCode] = $activeSlotRules->map(fn ($rule) => $rule->child?->product_id ?? $rule->child_id)->filter()->unique()->toArray();
        } else {
          $child = $activeSlotRules->first()?->child;
          $formValues[$roleCode] = $child instanceof ProductVariant ? $child->product_id : $child?->id;
        }
      }
    }

    return $formValues;
  }

  public static function configure(Schema $schema, string $pipelineCode, ?int $entityId = null, string $entityType = 'product_variant'): Schema
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
      $isScalar = empty($slot->typeCode) || $slot->typeCode === 'general';
      $displayLabel = $slot->labelKey;

      if ($isScalar) {
        $formFields[] = TextInput::make($roleCode)
          ->label($displayLabel)
          ->numeric()
          ->required($slot->isRequired);
      } else {
        $formFields[] = ProductSelect::make($roleCode)
          ->label($displayLabel)
          ->multiple($slot->isMultiple)
          ->options(function () use ($slot) {
            return Product::query()
              ->whereHas('type', fn ($q) => $q->where('code', $slot->typeCode))
              ->get()
              ->mapWithKeys(fn ($p) => [$p->id => ProductSelect::renderProductOption($p)])
              ->toArray();
          })
          ->required($slot->isRequired);
      }
    }

    return $schema->components([
      Grid::make(2)->schema($formFields)
    ]);
  }

  public static function save(array $data, int $entityId, string $pipelineCode, string $entityType = 'product_variant'): void
  {
    $pipeline = Pipeline::where('code', $pipelineCode)->first();
    if (!$pipeline) {
      return;
    }

    $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
    $entity = class_exists($morphClass) ? $morphClass::find($entityId) : null;
    $typeCode = $entity ? PipelineEntityResolver::resolveTypeCode($entity) : 'general';

    $treeService = app(PipelineTreeService::class);
    $pipelineSlots = $treeService->getPipelineSlots($pipelineCode);
    /** @var array<string, PipelineSlotDto> $slots */
    $slots = $pipelineSlots[$typeCode] ?? [];

    DB::transaction(function () use ($data, $entityId, $morphClass, $pipeline, $slots) {
      foreach ($slots as $roleCode => $slot) {
        $inputValue = $data[$roleCode] ?? null;
        $isScalar = empty($slot->typeCode) || $slot->typeCode === 'general';
        $displayLabel = $slot->labelKey;

        if ($slot->isMultiple) {
          $submittedProductIds = is_array($inputValue) ? $inputValue : [];
          $submittedVariantIds = ProductVariant::whereIn('product_id', $submittedProductIds)
            ->where('is_default', true)
            ->pluck('id')
            ->toArray();

          BindingRule::where('parent_type', $morphClass)
            ->where('parent_id', $entityId)
            ->where('pipeline_id', $pipeline->id)
            ->where('role', $roleCode)
            ->whereNotIn('child_id', $submittedVariantIds)
            ->delete();

          foreach ($submittedVariantIds as $childVariantId) {
            BindingRule::updateOrCreate([
              'pipeline_id' => $pipeline->id,
              'parent_type' => $morphClass,
              'parent_id' => $entityId,
              'role' => $roleCode,
              'child_type' => (new ProductVariant())->getMorphClass(),
              'child_id' => $childVariantId,
            ], [
              'external_code' => 'rule_' . md5($pipeline->id . $entityId . $childVariantId . $roleCode),
              'name' => __('Link') . ' ' . $displayLabel,
              'is_required' => $slot->isRequired,
            ]);
          }
        } else {
          if ($isScalar) {
            if (empty($inputValue)) {
              BindingRule::where('parent_type', $morphClass)
                ->where('parent_id', $entityId)
                ->where('role', $roleCode)
                ->delete();
            } else {
              BindingRule::updateOrCreate([
                'parent_type' => $morphClass,
                'parent_id' => $entityId,
                'role' => $roleCode,
              ], [
                'external_code' => 'rule_' . md5($entityId . $inputValue . $roleCode),
                'pipeline_id' => $pipeline->id,
                'name' => __('Parameter') . ' ' . $displayLabel,
                'child_type' => null,
                'child_id' => null,
                'static_meta' => [$roleCode => (string) $inputValue],
                'is_required' => $slot->isRequired,
              ]);
            }
          } else {
            if (empty($inputValue)) {
              BindingRule::where('parent_type', $morphClass)
                ->where('parent_id', $entityId)
                ->where('pipeline_id', $pipeline->id)
                ->where('role', $roleCode)
                ->delete();
            } else {
              $childProduct = Product::find($inputValue);
              $childVariantId = $childProduct?->variants()->where('is_default', true)->value('id') ?? $inputValue;

              BindingRule::updateOrCreate([
                'pipeline_id' => $pipeline->id,
                'parent_type' => $morphClass,
                'parent_id' => $entityId,
                'role' => $roleCode,
              ], [
                'external_code' => 'rule_' . md5($pipeline->id . $entityId . $childVariantId . $roleCode),
                'name' => __('Link') . ' ' . $displayLabel,
                'child_type' => (new ProductVariant())->getMorphClass(),
                'child_id' => $childVariantId,
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
