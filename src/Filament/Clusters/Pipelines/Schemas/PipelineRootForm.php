<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

class PipelineRootForm
{
    protected static function resolveLabel(mixed $label): string
    {
        return is_array($label)
            ? ($label[app()->getLocale()] ?? ($label['ru'] ?? (string) head($label)))
            : __((string) $label);
    }

    public static function fill(int $variantId, string $pipelineCode): array
    {
        $pipeline = Pipeline::where('code', $pipelineCode)->first();
        if (!$pipeline) {
            return [];
        }

        $rules = BindingRule::where('parent_type', (new ProductVariant())->getMorphClass())
            ->where('parent_id', $variantId)
            ->get();

        $formValues = [];

        $variant = ProductVariant::find($variantId);
        $typeCode = $variant?->product?->type?->code ?? 'general';

        $pipelineSchema = app(PipelineTreeService::class)->getPipelineSchema($pipelineCode);
        $schema = $pipelineSchema[$typeCode] ?? [];

        foreach ($schema as $roleCode => $slotMeta) {
            $slotRules = $rules->where('role', $roleCode);
            $isScalar = empty($slotMeta['type_code']) || $slotMeta['type_code'] === 'general';

            if ($isScalar) {
                $formValues[$roleCode] = !empty($slotRules->first()?->static_meta)
                    ? head($slotRules->first()->static_meta)
                    : null;
            } else {
                $activeSlotRules = $slotRules->where('pipeline_id', $pipeline->id);
                if (!empty($slotMeta['is_multiple'])) {
                    $formValues[$roleCode] = $activeSlotRules->map(fn ($rule) => $rule->child?->product_id)->filter()->unique()->toArray();
                } else {
                    $formValues[$roleCode] = $activeSlotRules->first()?->child?->product_id;
                }
            }
        }

        return $formValues;
    }

    public static function configure(Schema $schema, string $pipelineCode, ?int $variantId = null): Schema
    {
        $typeCode = 'general';
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            $typeCode = $variant?->product?->type?->code ?? 'general';
        }

        $pipelineSchema = app(PipelineTreeService::class)->getPipelineSchema($pipelineCode);
        $schemaData = $pipelineSchema[$typeCode] ?? [];

        $formFields = [];

        foreach ($schemaData as $roleCode => $slotMeta) {
            $isScalar = empty($slotMeta['type_code']) || $slotMeta['type_code'] === 'general';
            $displayLabel = static::resolveLabel($slotMeta['label_key'] ?? '');

            if ($isScalar) {
                $formFields[] = TextInput::make($roleCode)
                    ->label($displayLabel)
                    ->numeric()
                    ->required((bool) $slotMeta['is_required']);
            } else {
                $formFields[] = ProductSelect::make($roleCode)
                    ->label($displayLabel)
                    ->multiple(!empty($slotMeta['is_multiple']))
                    ->options(function () use ($slotMeta) {
                        return Product::query()
                            ->whereHas('type', fn ($q) => $q->where('code', $slotMeta['type_code']))
                            ->get()
                            ->mapWithKeys(fn ($p) => [$p->id => ProductSelect::renderProductOption($p)])
                            ->toArray();
                    })
                    ->required((bool) $slotMeta['is_required']);
            }
        }

        return $schema->components([
            Grid::make(2)->schema($formFields)
        ]);
    }

    public static function save(array $data, int $variantId, string $pipelineCode): void
    {
        $pipeline = Pipeline::where('code', $pipelineCode)->first();
        if (!$pipeline) {
            return;
        }

        $parentType = (new ProductVariant())->getMorphClass();
        $variant = ProductVariant::find($variantId);
        $typeCode = $variant?->product?->type?->code ?? 'general';

        $pipelineSchema = app(PipelineTreeService::class)->getPipelineSchema($pipelineCode);
        $schema = $pipelineSchema[$typeCode] ?? [];

        DB::transaction(function () use ($data, $variantId, $parentType, $pipeline, $schema) {
            foreach ($schema as $roleCode => $slotMeta) {
                $inputValue = $data[$roleCode] ?? null;
                $isScalar = empty($slotMeta['type_code']) || $slotMeta['type_code'] === 'general';
                $displayLabel = static::resolveLabel($slotMeta['label_key'] ?? '');

                if (!empty($slotMeta['is_multiple'])) {
                    $submittedProductIds = is_array($inputValue) ? $inputValue : [];
                    $submittedVariantIds = ProductVariant::whereIn('product_id', $submittedProductIds)
                        ->where('is_default', true)
                        ->pluck('id')
                        ->toArray();

                    BindingRule::where('parent_type', $parentType)
                        ->where('parent_id', $variantId)
                        ->where('pipeline_id', $pipeline->id)
                        ->where('role', $roleCode)
                        ->whereNotIn('child_id', $submittedVariantIds)
                        ->delete();

                    foreach ($submittedVariantIds as $childVariantId) {
                        BindingRule::updateOrCreate([
                            'pipeline_id' => $pipeline->id,
                            'parent_type' => $parentType,
                            'parent_id' => $variantId,
                            'role' => $roleCode,
                            'child_type' => (new ProductVariant())->getMorphClass(),
                            'child_id' => $childVariantId,
                        ], [
                            'external_code' => 'rule_' . md5($pipeline->id . $variantId . $childVariantId . $roleCode),
                            'name' => __('Link') . ' ' . $displayLabel,
                            'is_required' => (bool) $slotMeta['is_required'],
                        ]);
                    }
                } else {
                    if ($isScalar) {
                        if (empty($inputValue)) {
                            BindingRule::where('parent_type', $parentType)
                                ->where('parent_id', $variantId)
                                ->where('role', $roleCode)
                                ->delete();
                        } else {
                            BindingRule::updateOrCreate([
                                'parent_type' => $parentType,
                                'parent_id' => $variantId,
                                'role' => $roleCode,
                            ], [
                                'external_code' => 'rule_' . md5($variantId . $inputValue . $roleCode),
                                'pipeline_id' => $pipeline->id,
                                'name' => __('Parameter') . ' ' . $displayLabel,
                                'child_type' => null,
                                'child_id' => null,
                                'static_meta' => [$roleCode => (string) $inputValue],
                                'is_required' => (bool) $slotMeta['is_required'],
                            ]);
                        }
                    } else {
                        if (empty($inputValue)) {
                            BindingRule::where('parent_type', $parentType)
                                ->where('parent_id', $variantId)
                                ->where('pipeline_id', $pipeline->id)
                                ->where('role', $roleCode)
                                ->delete();
                        } else {
                            $childProduct = Product::find($inputValue);
                            $childVariantId = $childProduct?->variants()->where('is_default', true)->value('id') ?? $inputValue;

                            BindingRule::updateOrCreate([
                                'pipeline_id' => $pipeline->id,
                                'parent_type' => $parentType,
                                'parent_id' => $variantId,
                                'role' => $roleCode,
                            ], [
                                'external_code' => 'rule_' . md5($pipeline->id . $variantId . $childVariantId . $roleCode),
                                'name' => __('Link') . ' ' . $displayLabel,
                                'child_type' => (new ProductVariant())->getMorphClass(),
                                'child_id' => $childVariantId,
                                'is_required' => (bool) $slotMeta['is_required'],
                            ]);
                        }
                    }
                }
            }
        });

        Notification::make()->title(__('Configuration saved successfully'))->success()->send();
    }
}
