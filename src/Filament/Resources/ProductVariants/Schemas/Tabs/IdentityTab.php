<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;
use Livewire\Component;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use Nicole\Box\Core\Services\Catalog\SkuGeneratorService;

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
                ->nullable()
                ->placeholder(__('Auto-generated if left blank'))
                ->helperText(__('Leave blank to generate automatically according to product code.'))
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(function ($state, Get $get, ?Model $record, ?Component $livewire) {
                  if (filled($state)) {
                    return $state;
                  }
                  $product = ProductVariantForm::resolveProduct($get, $record, $livewire);
                  return app(SkuGeneratorService::class)->generate($product);
                })
                ->maxLength(255),

              TextInput::make('name')
                ->label(__('Variant Name'))
                ->placeholder(__('Leave empty to inherit parent product name'))
                ->helperText(__('If left empty, the parent product name will be used.'))
                ->translatable()
                ->columnSpanFull(),

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
