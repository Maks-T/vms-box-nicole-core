<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Categories\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Models\Category;

class CategoriesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label(__('Name'))
          ->formatStateUsing(
            fn ($record, $state) => str_repeat(
                '- ',
                max(0, (int) $record->depth),
              ).$state,
          )
          ->searchable(['name', 'slug', 'external_code'])
          ->sortable(),


        TextColumn::make('parent.name')
          ->label(__('Parent Category'))
          ->badge()
          ->color('gray')
          ->searchable()
          ->sortable()
          ->toggleable(),

        TableHelper::codeColumn('slug'), // Shared


        TextColumn::make('external_code')
          ->label(__('External Code'))
          ->fontFamily('mono')
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TableHelper::statusColumn(), // Shared

        TextColumn::make('products_count')
          ->label(__('Products'))
          ->counts('products')
          ->badge()
          ->color('info'),
      ])
      ->filters([
        FilterHelper::activeFilter(), // Shared

        FilterHelper::selectFilter(
          'parent_id',
          __('Parent Category'),
          fn () => Category::where('id', '!=', 0)->pluck('name', 'id')->toArray(),
        ),
      ])
      // Для вложенных множеств (NestedSet) сортировка по _lft строго обязательна, чтобы сохранять дерево!
      ->defaultSort('_lft', 'asc');
  }
}
