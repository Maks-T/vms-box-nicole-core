<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\PriceTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;

class PriceTypesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),

        TextColumn::make('currency.code')
          ->label(__('Currency'))
          ->badge()
          ->color('success')
          ->sortable(),

        TextColumn::make('slug')
          ->label(__('Slug'))
          ->badge()
          ->color('info')
          ->fontFamily('mono'),


        TextColumn::make('external_code')
          ->label(__('External Code'))
          ->fontFamily('mono')
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        IconColumn::make('is_default')->label(__('Default'))->boolean(),

        TextColumn::make('updated_at')
          ->label(__('Updated At'))
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
      ])

      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->recordActions([
        EditAction::make(),
        ProtectDefaultRecord::tableDeleteAction('Cannot delete base currency'),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          ProtectDefaultRecord::tableBulkDeleteAction('Base currencies skipped'),
        ]),
      ]);
  }
}
