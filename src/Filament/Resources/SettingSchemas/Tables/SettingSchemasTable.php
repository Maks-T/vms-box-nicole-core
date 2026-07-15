<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\SettingSchemas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Models\SettingSchema;

class SettingSchemasTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('entity_type')
          ->label(__('Entity Type'))
          ->badge()
          ->color('info')
          ->searchable()
          ->sortable(),

        TextColumn::make('fields_count')
          ->label(__('Fields defined'))
          ->state(
          
            fn (SettingSchema $record): int => is_array($record->meta_schema)
              ? count($record->meta_schema)
              : 0,
          )
          ->badge()
          ->color('gray'),

        TextColumn::make('updated_at')
          ->label(__('Updated At'))
          ->dateTime()
          ->sortable()
          ->toggleable(),

        TextColumn::make('created_at')
          ->label(__('Created At'))
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([])
      ->recordActions([EditAction::make()])
      ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
  }
}
