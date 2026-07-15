<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

class InventoryTab
{
  public static function make(): Tab
  {
    return Tab::make(__('Inventory by Warehouses'))
      ->icon('heroicon-o-home-modern')
      ->schema([
        Repeater::make('stocks')
          ->label(__('Warehouse Allocations'))
          ->relationship('stocks')
          ->schema([
            Select::make('warehouse_id')
              ->label(__('Warehouse'))
              ->relationship('warehouse', 'name')
              ->required()
              ->distinct()
              ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

            TextInput::make('quantity')
              ->label(__('Physical Quantity'))
              ->numeric()
              ->default(0)
              ->required(),

            TextInput::make('reserved')
              ->label(__('Reserved'))
              ->numeric()
              ->default(0)
              ->disabled()
              ->dehydrated(false)
              ->helperText(__('Locked by active orders')),
          ])
          ->columns(3)
          ->defaultItems(0)
          ->addActionLabel(__('Add Warehouse Stock')),
      ]);
  }
}
