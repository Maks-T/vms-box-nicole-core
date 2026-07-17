<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Filament\Resources\Products\Filters\ProductFilters;
use Nicole\Box\Core\Services\PricingManager;

class ProductsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TableHelper::idColumn(),

        TableHelper::photoColumn(), // Shared

        TextColumn::make('name')
          ->label(__('Name'))
          ->searchable(['name', 'slug', 'external_code'])
          ->sortable()
          ->toggleable()
          ->wrap(),

        TableHelper::externalCodeColumn(),

        TableHelper::codeColumn('code')
          ->label(__('Code') . ' / Артикул'),

        TableHelper::codeColumn('slug')
          ->label('Символьный код (Slug)'),

        TextColumn::make('category.name')
          ->label(__('Category'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true)
          ->sortable(),

        TextColumn::make('type.name')
          ->label(__('Product Type'))
          ->badge()
          ->toggleable(),

        TextColumn::make('catalog_type')
          ->label('Тип позиции')
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('unit.name')
          ->label(__('Unit'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true)
          ->sortable(),

        TextColumn::make('short_description')
          ->label(__('Short Description'))
          ->limit(30)
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('description')
          ->label(__('Description'))
          ->limit(30)
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('min_price')
          ->label(__('Price From'))
          ->money(fn() => app(PricingManager::class)->baseCurrency->code)
          ->sortable()
          ->toggleable(),

        TableHelper::statusColumn(), // Активность (Toggle)

        TextColumn::make('variants_count')
          ->label(__('SKUs'))
          ->counts('variants')
          ->badge()
          ->color('info')
          ->toggleable(),

        TableHelper::sortOrderColumn(),
        TableHelper::createdAtColumn(),
        TableHelper::updatedAtColumn(),
      ])
      ->columnManagerColumns(2)
      ->filtersLayout(FiltersLayout::AboveContent)
      ->filtersFormColumns(3)
      ->filters(ProductFilters::all())
      ->persistFiltersInSession()
      ->persistSearchInSession()
      ->persistSortInSession();
  }
}
