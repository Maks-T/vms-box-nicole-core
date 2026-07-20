<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Pipelines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class PipelinesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label(__('Name'))
          ->searchable()
          ->sortable(),

        TextColumn::make('code')
          ->label(__('System Code'))
          ->searchable()
          ->sortable(),

        TextColumn::make('industry')
          ->label(__('Industry'))
          ->badge()
          ->color('info')
          ->sortable(),

        IconColumn::make('is_active')
          ->label(__('Is Active'))
          ->boolean()
          ->sortable(),

        TextColumn::make('sort_order')
          ->label(__('Sort Order'))
          ->numeric()
          ->sortable(),
      ])
      ->filters([
        //
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
