<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Forms\Tabs;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductType;

class LinkedItemsTab
{
    public static function make(): Tab
    {
        return Tab::make(__('Linked Items'))
            ->icon('heroicon-o-puzzle-piece')
            ->schema([
                Repeater::make('linkedItems')
                    ->relationship('linkedItems')
                    ->label('')
                    ->addActionLabel(__('Add Component or Service'))
                    ->reorderable()
                    ->schema([
                        Select::make('temp_product_type')
                            ->label(__('Type'))
                            ->options(fn () => static::productTypeOptions())
                            ->live()
                            ->dehydrated(false)
                            ->columnSpan(3)
                            ->afterStateHydrated(function (?Model $record, Set $set) {
                                if ($record && isset($record->child_id)) {
                                    $productTypeId = DB::table('products')
                                        ->where('id', $record->child_id)
                                        ->value('product_type_id');
                                    if ($productTypeId) {
                                        $set('temp_product_type', $productTypeId);
                                    }
                                }
                            })
                            ->afterStateUpdated(fn (Set $set) => $set('child_id', null)),

                        ProductSelect::make('child_id')
                            ->label(__('Linked Product'))
                            ->required()
                            // @since 2026-11-06: первые 50 для прокрутки + серверный поиск по типу, с eager loading.
                            ->options(function (Get $get) {
                                $typeId = $get('temp_product_type');
                                if (! $typeId) {
                                    return [];
                                }
                                return static::linkedProductsQuery((int) $typeId)
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => ProductSelect::renderProductOption($p)])
                                    ->all();
                            })
                            ->getSearchResultsUsing(function (string $search, Get $get) {
                                $typeId = $get('temp_product_type');
                                if (! $typeId) {
                                    return [];
                                }

                                $locale = app()->getLocale();

                                return static::linkedProductsQuery((int) $typeId)
                                    ->where(fn ($q) => $q
                                        ->where("name->{$locale}", 'ilike', "%{$search}%")
                                        ->orWhere('code', 'ilike', "%{$search}%"))
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => ProductSelect::renderProductOption($p)])
                                    ->all();
                            })
                            ->columnSpan(7),

                        TextInput::make('quantity_formula')
                            ->label(__('Qty'))
                            ->default('1')
                            ->required()
                            ->columnSpan(2),

                      Hidden::make('child_type')->default(new Product()->getMorphClass()),
                    ])
                    ->columns(12)
                    ->defaultItems(0),
            ]);
    }

    /**
     * @var array<int|string, string>|null
     * @since 2026-11-06
     */
    private static ?array $productTypeOptions = null;

    /**
     * Типы товаров - один запрос на HTTP-запрос
     *
     * @return array<int|string, string>
     * @since 2026-11-06
     */
    protected static function productTypeOptions(): array
    {
        return self::$productTypeOptions ??= ProductType::query()->pluck('name', 'id')->all();
    }

    /**
     * Запрос товаров для селекта.
     *
     * @since 2026-11-06
     */
    protected static function linkedProductsQuery(int $typeId): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->select(['id', 'name', 'code', 'min_price', 'product_type_id', 'sort_order'])
            ->where('product_type_id', $typeId)
            ->with([
                'media',
                'variants' => fn ($q) => $q->select(['id', 'product_id', 'is_default', 'is_active', 'sort_order'])->where('is_active', true),
                'variants.media',
            ])
            ->orderBy('sort_order');
    }
}
