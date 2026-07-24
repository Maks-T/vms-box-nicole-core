<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas\PipelineRootForm;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

class ConfigureNodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'configureNode';
    }

    protected function resolveVariantId(array $arguments): ?int
    {
        if (!empty($arguments['variant_id'])) {
            return (int) $arguments['variant_id'];
        }

        if (!empty($arguments['rule_id'])) {
            $rule = BindingRule::find($arguments['rule_id']);
            if ($rule && $rule->parent_id) {
                return (int) $rule->parent_id;
            }
        }

        return null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->modalHeading(function (array $arguments) {
            $virtualMeta = $arguments['virtual_meta'] ?? [];
            if (!empty($virtualMeta)) {
                return __('Create Connection');
            }

            $variantId = $this->resolveVariantId($arguments);
            $variant = $variantId ? ProductVariant::find($variantId) : null;
            $groupName = $variant?->product?->type?->getTranslation('name', app()->getLocale()) ?? __('Group');
            return __('Configure') . ": {$groupName} — " . ($variant?->name ?? '');
        })
            ->modalWidth(Width::SevenExtraLarge)
            ->fillForm(function (array $arguments) {
                $virtualMeta = $arguments['virtual_meta'] ?? [];
                if (!empty($virtualMeta)) {
                    return [
                        'child_id' => null,
                        'quantity_formula' => '1',
                        'static_meta' => [],
                    ];
                }

                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                $variantId = $this->resolveVariantId($arguments) ?? 0;
                return PipelineRootForm::fill($variantId, $config['pipeline_code']);
            })
            ->schema(function (Schema $schema, array $arguments) {
                $virtualMeta = $arguments['virtual_meta'] ?? [];
                if (!empty($virtualMeta)) {
                    return $schema->components([
                        Grid::make(2)->schema([
                            ProductSelect::make('child_id')
                                ->label(__('Linked Product / Service'))
                                ->options(function () use ($virtualMeta) {
                                    return Product::query()
                                        ->whereHas('type', fn ($q) => $q->where('code', $virtualMeta['type_code']))
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [$p->id => ProductSelect::renderProductOption($p)])
                                        ->toArray();
                                })
                                ->required(),

                            Section::make(__('Advanced Settings'))
                                ->collapsed()
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('quantity_formula')
                                            ->label(__('Quantity Formula'))
                                            ->default('1')
                                            ->required(),

                                        KeyValue::make('static_meta')
                                            ->label(__('Technical Parameters'))
                                            ->columnSpanFull(),
                                    ])
                                ])
                        ])
                    ]);
                }

                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                $variantId = $this->resolveVariantId($arguments) ?? 0;
                return PipelineRootForm::configure($schema, $config['pipeline_code'], $variantId);
            })
            ->action(function (array $data, array $arguments) {
                $virtualMeta = $arguments['virtual_meta'] ?? [];
                if (!empty($virtualMeta)) {
                    $childProduct = Product::find($data['child_id']);
                    $childVariantId = $childProduct?->variants()->where('is_default', true)->value('id') ?? $data['child_id'];

                    BindingRule::create([
                        'pipeline_id' => $virtualMeta['pipeline_id'] ?? null,
                        'parent_type' => $virtualMeta['parent_type'],
                        'parent_id' => $virtualMeta['parent_id'],
                        'role' => $virtualMeta['role'],
                        'child_type' => (new ProductVariant())->getMorphClass(),
                        'child_id' => $childVariantId,
                        'quantity_formula' => $data['quantity_formula'] ?? '1',
                        'static_meta' => $data['static_meta'] ?? null,
                        'is_required' => true,
                    ]);

                    Notification::make()->title(__('Connection created successfully'))->success()->send();
                    return;
                }

                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                $variantId = $this->resolveVariantId($arguments) ?? 0;
                PipelineRootForm::save($data, $variantId, $config['pipeline_code']);
            });
    }
}
