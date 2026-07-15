<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Models\ProductFamily;

class ProductTypesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')->label(__('Name'))->sortable()->searchable(),

        TableHelper::codeColumn('code'),


        TextColumn::make('slug')
          ->label(__('Slug'))
          ->fontFamily('mono')
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('family.name')
          ->label(__('Family'))
          ->badge()
          ->color('gray')
          ->sortable(),

        TableHelper::statusColumn(), // Shared
      ])
      ->filters([
        FilterHelper::activeFilter(), // Shared

        FilterHelper::selectFilter(
          'family_id',
          __('Family'),
          fn () => ProductFamily::pluck('name', 'id'),
        ),
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc');
  }
}
