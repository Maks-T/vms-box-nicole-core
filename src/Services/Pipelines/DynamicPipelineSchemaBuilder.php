<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Pipelines;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\Product;

class DynamicPipelineSchemaBuilder
{
    /**
     * Динамическая секция Триггеров (Входные условия)
     */
    public static function getTriggersSection(): Section
    {
        return Section::make(__('Configuration (Triggers)'))
            ->description(__('Select camera and system parameters that trigger this scenario'))
            ->schema([
                Grid::make(3)->schema([
                    Select::make('ui_state.groups')
                        ->label(__('Camera Groups'))
                        ->multiple()
                        ->options(fn (): array => self::getOptionList('camera_groups'))
                        ->preload(),

                    Select::make('ui_state.resolutions')
                        ->label(__('Camera Resolutions'))
                        ->multiple()
                        ->options(fn (): array => self::getOptionList('camera_resolution'))
                        ->preload(),

                    Grid::make(2)->schema([
                        TextInput::make('ui_state.range_from')
                            ->label(__('Range From'))
                            ->numeric()
                            ->default(1),

                        TextInput::make('ui_state.range_to')
                            ->label(__('Range To'))
                            ->numeric()
                            ->default(4),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    /**
     * Динамическая генерация вкладок хранения на основе EAV-атрибута storage_time
     */
    public static function getStorageTabsComponent(): Tabs
    {
        $storageOptions = AttributeOption::query()
            ->whereHas('attribute', fn ($q) => $q->whereIn('code', ['storage_time', 'storage-time']))
            ->orderBy('sort_order')
            ->get();

        $tabs = [];

        foreach ($storageOptions as $option) {
            $daysKey = $option->param > 0 ? (int) ($option->param / 24) : $option->slug;
            $tabLabel = $option->getTranslation('value', app()->getLocale()) ?? "{$daysKey} " . __('days');

            $tabs[] = Tabs\Tab::make("tab_{$daysKey}_days")
                ->label($tabLabel)
                ->icon('heroicon-o-server')
                ->schema([
                    ProductSelect::make("ui_state.storage.{$daysKey}.product_id")
                        ->label(__('Recorder (NVR)'))
                        ->options(fn (): array => self::getProductsByTypeCode('recorder'))
                        ->searchable(),

                    Repeater::make("ui_state.storage.{$daysKey}.memory")
                        ->label(__('Memory (HDDs)'))
                        ->schema([
                            ProductSelect::make('product_id')
                                ->label(__('HDD Model'))
                                ->options(fn (): array => self::getProductsByTypeCode('storage'))
                                ->searchable()
                                ->required(),

                            TextInput::make('quantity')
                                ->label(__('Quantity Multiplier'))
                                ->helperText(__('Multiplied by total camera count'))
                                ->numeric()
                                ->default(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel(__('Add Memory')),
                ]);
        }

        return Tabs::make('StorageDaysTabs')
            ->tabs($tabs)
            ->columnSpanFull();
    }

    private static function getOptionList(string $attributeCode): array
    {
        return AttributeOption::query()
            ->whereHas('attribute', fn ($q) => $q->where('code', $attributeCode))
            ->get()
            ->pluck('value', 'id')
            ->toArray();
    }

    private static function getProductsByTypeCode(string $typeCode): array
    {
        return Product::query()
            ->whereHas('type', fn ($q) => $q->where('code', $typeCode))
            ->get()
            ->mapWithKeys(fn (Product $p): array => [$p->id => ProductSelect::renderProductOption($p)])
            ->toArray();
    }
}
