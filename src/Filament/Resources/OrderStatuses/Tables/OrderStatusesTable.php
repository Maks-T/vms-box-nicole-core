<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;


class OrderStatusesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label(__('Name'))
          ->searchable()
          ->sortable()
          ->weight('bold'),

        TextColumn::make('slug')
          ->label(__('Slug'))
          ->fontFamily('mono')
          ->color('gray')
          ->searchable(),

        TextColumn::make('color')
          ->label(__('Color'))
          ->badge()
          // Динамически окрашиваем бейдж в цвет Filament, соответствующий статусу!
          ->color(fn ($state) => $state)
          ->searchable(),

        IconColumn::make('is_default')
          ->label(__('Default'))
          ->boolean(),

        IconColumn::make('is_active')
          ->label(__('Is Active'))
          ->boolean(),

        TextColumn::make('sort_order')
          ->label(__('Sort'))
          ->numeric()
          ->sortable(),
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->recordActions([
        EditAction::make(),
        // Защищаем дефолтный статус от удаления в строке таблицы
        ProtectDefaultRecord::tableDeleteAction('Cannot delete default status'),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          // Защищаем дефолтный статус от массового удаления
          ProtectDefaultRecord::tableBulkDeleteAction('Default statuses skipped'),
        ]),
      ]);
  }
}
