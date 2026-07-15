<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),

        TextColumn::make('symbol')
          ->label(__('Symbol'))
          ->badge()
          ->color('info')
          ->searchable(),

        TextColumn::make('slug')
          ->label(__('Slug'))
          ->fontFamily('mono')
          ->color('gray')
          ->searchable(),

        TextColumn::make('code')
          ->label(__('Standard Code'))
          ->fontFamily('mono')
          ->color('gray')
          ->searchable(),

        TextColumn::make('updated_at')
          ->label(__('Updated At'))
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->recordActions([EditAction::make()])
      ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
  }
}
