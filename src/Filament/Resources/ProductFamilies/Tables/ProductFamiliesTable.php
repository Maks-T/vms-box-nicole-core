<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductFamilies\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\TableHelper;

class ProductFamiliesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')->label(__('Name'))->sortable()->searchable(),

        TableHelper::codeColumn('code'), // Shared

        TextColumn::make('slug')
          ->label(__('Slug'))
          ->fontFamily('mono')
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        IconColumn::make('icon')
          ->label(__('Icon'))
          ->icon(fn (?string $state): ?string => $state)
          ->color('gray'),

        TableHelper::statusColumn(), // Shared
      ])
      ->filters([
        FilterHelper::activeFilter(), // Shared
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc');
  }
}
