<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        
        TextColumn::make('full_name')
          ->label(__('Name'))
          ->state(fn ($record) => $record->full_name)
          ->searchable(query: function (Builder $query, string $search): Builder {
            return $query->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('middle_name', 'like', "%{$search}%");
          })
          ->sortable(['last_name', 'first_name'])
          ->weight('bold'),

        TextColumn::make('phone')
          ->label(__('Phone'))
          ->searchable()
          ->copyable(),

        TextColumn::make('email')
          ->label('Email address')
          ->searchable()
          ->copyable()
          ->color('gray'),

        TextColumn::make('address')
          ->label(__('Address'))
          ->limit(40)
          ->searchable()
          ->toggleable(),

        TextColumn::make('created_at')
          ->label(__('Created At'))
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
