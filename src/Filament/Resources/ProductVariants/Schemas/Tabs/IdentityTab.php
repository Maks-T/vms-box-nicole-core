<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\RelationManagers\RelationManager;
use Livewire\Component;

class IdentityTab
{
  public static function make(): Tab
  {
    return Tab::make(__('Identity & Status'))
      ->icon('heroicon-o-tag')
      ->schema([
        Grid::make(3)->schema([
          Section::make(__('Variant Identity'))
            ->columnSpan(2)
            ->schema([
              Select::make('product_id')
                ->label(__('Parent Product'))
                ->relationship('product', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (string $context) => $context === 'edit')
                ->hidden(
                  fn (Component $livewire) => $livewire instanceof RelationManager,
                ),

              TextInput::make('sku')
                ->label(__('SKU / Article'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

              TextInput::make('external_code')
                ->label(__('External Code'))
                ->nullable()
                ->helperText(__('Used for API / 1C integrations')),
            ])
            ->columns(2),

          Section::make(__('Status'))
            ->columnSpan(1)
            ->schema([
              Toggle::make('is_default')
                ->label(__('Default Variant'))
                ->helperText(__('Selected by default in the catalog')),

              Toggle::make('is_active')
                ->label(__('Is Active'))
                ->default(true),
            ]),
        ]),
      ]);
  }
}
