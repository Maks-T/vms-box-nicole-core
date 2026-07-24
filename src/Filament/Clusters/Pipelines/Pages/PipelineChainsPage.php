<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Actions\ActivateTreeAction;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Actions\ConfigureNodeAction;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Actions\ConfigureRootAction;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Actions\DeleteNodeAction;
use Nicole\Box\Core\Filament\Clusters\Pipelines\PipelineCluster;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

class PipelineChainsPage extends Page implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;

    protected static ?string $cluster = PipelineCluster::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;
    protected static ?int $navigationSort = 2;

    protected string $view = 'nicole-core::filament.clusters.pipelines.pages.pipeline-chains-page';

    #[Url(as: 'pipeline')]
    public ?string $pipeline_code = null;

    #[Url]
    public ?int $base_variant_id = null;

    public static function getNavigationLabel(): string
    {
        return __('Chains & Visual Tree');
    }

    public static function getBreadcrumb(): string
    {
        return __('Chains & Visual Tree');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '' => __('Configurations'),
        ];
    }

    public function getTitle(): string
    {
        return __('Chains & Visual Tree');
    }

    public function mount(): void
    {
        if (!$this->pipeline_code) {
            $defaultPipeline = Pipeline::where('is_active', true)->orderBy('sort_order')->first();
            $this->pipeline_code = $defaultPipeline?->code;
        }
    }

    public function updatedPipelineCode(): void
    {
        $this->unmountAction();
        $this->redirect(static::getUrl([
            'pipeline' => $this->pipeline_code,
        ]));
    }

    public function selectVariant(int $variantId): void
    {
        $this->unmountAction();
        $this->redirect(static::getUrl([
            'pipeline' => $this->pipeline_code,
            'base_variant_id' => $variantId,
        ]));
    }

    public function closeTree(): void
    {
        $this->unmountAction();
        $this->redirect(static::getUrl([
            'pipeline' => $this->pipeline_code,
        ]));
    }

    /** @noinspection PhpUnused */
    public function configureNodeAction(): Action { return ConfigureNodeAction::make(); }
    /** @noinspection PhpUnused */
    public function activateTreeAction(): Action { return ActivateTreeAction::make(); }
    /** @noinspection PhpUnused */
    public function deleteNodeAction(): Action { return DeleteNodeAction::make(); }
    /** @noinspection PhpUnused */
    public function configureRootAction(): Action { return ConfigureRootAction::make(); }

    public function getPipelineConfig(): array
    {
        $pipeline = Pipeline::where('code', $this->pipeline_code)->first();
        return [
            'pipeline_code' => $this->pipeline_code,
            'pipeline_id' => $pipeline?->id,
        ];
    }

    protected function resolveRootTypeCode(?Pipeline $pipeline): ?string
    {
        if (!$pipeline) return null;

        $treeService = app(PipelineTreeService::class);
        $schema = $treeService->getPipelineSchema($pipeline->code, $pipeline);

        if (empty($schema)) return null;

        $allParents = array_keys($schema);
        $allChildren = [];

        foreach ($schema as $slots) {
            foreach ($slots as $slot) {
                if (!empty($slot['type_code'])) {
                    $allChildren[] = $slot['type_code'];
                }
            }
        }

        $rootTypes = array_values(array_diff($allParents, array_unique($allChildren)));

        return $rootTypes[0] ?? array_key_first($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (!$this->pipeline_code) {
                    return ProductVariant::query()->whereRaw('1 = 0');
                }

                $pipeline = Pipeline::where('code', $this->pipeline_code)->first();
                if (!$pipeline) {
                    return ProductVariant::query()->whereRaw('1 = 0');
                }

                $rootTypeCode = $this->resolveRootTypeCode($pipeline);

                $variantIds = BindingRule::where('pipeline_id', $pipeline->id)
                    ->where('parent_type', (new ProductVariant())->getMorphClass())
                    ->pluck('parent_id')
                    ->unique();

                $query = ProductVariant::query()->whereIn('id', $variantIds);

                if ($rootTypeCode) {
                    $query->whereHas('product.type', fn ($q) => $q->where('code', $rootTypeCode));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Root Product'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->fontFamily('mono')
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('tree_validity')
                    ->label(__('Tree State'))
                    ->state(function (ProductVariant $record): string {
                        if (!$this->pipeline_code) return __('Has Errors');

                        $report = app(PipelineTreeService::class)->analyzeTree($record->id, $this->pipeline_code);
                        return $report && ($report['is_valid'] ?? false)
                            ? __('Ready to Publish')
                            : __('Has Errors');
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        __('Ready to Publish') => 'success',
                        default => 'danger',
                    }),

                IconColumn::make('is_active')
                    ->label(__('Is Active'))
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('configure_tree')
                    ->label(__('Configure Tree'))
                    ->icon('heroicon-m-sparkles')
                    ->color('primary')
                    ->action(fn (ProductVariant $record) => $this->selectVariant($record->id)),
            ])
            ->headerActions([
                Action::make('create_chain')
                    ->label(__('Create Chain'))
                    ->icon('heroicon-m-plus')
                    ->modalHeading(__('Select Root SKU to Configure'))
                    ->schema([
                        Select::make('variant_id')
                            ->label(__('Root SKU'))
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $pipeline = Pipeline::where('code', $this->pipeline_code)->first();
                                $rootTypeCode = $this->resolveRootTypeCode($pipeline);

                                $configuredVariantIds = BindingRule::where('pipeline_id', $pipeline?->id)
                                    ->where('parent_type', (new ProductVariant())->getMorphClass())
                                    ->pluck('parent_id')
                                    ->toArray();

                                $query = ProductVariant::query()->whereNotIn('id', $configuredVariantIds);

                                if ($rootTypeCode) {
                                    $query->whereHas('product.type', fn ($q) => $q->where('code', $rootTypeCode));
                                }

                                return $query->get()
                                    ->mapWithKeys(fn ($v) => [$v->id => "{$v->product?->name} ({$v->sku})"])
                                    ->toArray();
                            })
                    ])
                    ->action(function (array $data) {
                        $pipeline = Pipeline::where('code', $this->pipeline_code)->first();
                        $variantId = (int) $data['variant_id'];

                        BindingRule::create([
                            'pipeline_id' => $pipeline?->id,
                            'parent_type' => (new ProductVariant())->getMorphClass(),
                            'parent_id' => $variantId,
                            'role' => 'root',
                            'name' => __('Root Connection'),
                            'is_required' => true,
                        ]);

                        Notification::make()
                            ->title(__('Configuration chain created'))
                            ->success()
                            ->send();

                        $this->selectVariant($variantId);
                    }),
            ]);
    }
}
