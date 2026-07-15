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
                            ->options(ProductType::query()->pluck('name', 'id'))
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
                            ->options(function (Get $get) {
                                $typeId = $get('temp_product_type');
                                if (! $typeId) {
                                    return [];
                                }

                                return Product::query()
                                    ->where('product_type_id', $typeId)
                                    ->get()
                                    ->mapWithKeys(
                                        fn ($p) => [
                                            $p->id => ProductSelect::renderProductOption($p),
                                        ],
                                    );
                            })
                            ->columnSpan(7),

                        TextInput::make('quantity_formula')
                            ->label(__('Qty'))
                            ->default('1')
                            ->required()
                            ->columnSpan(2),

                        Hidden::make('child_type')->default(Product::class),
                    ])
                    ->columns(12)
                    ->defaultItems(0),
            ]);
    }
}
