<?php

namespace Nicole\Box\Core\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('Slug'))->badge()->color('gray'),
                TextColumn::make('address')
                    ->label(__('Physical Address'))
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('phone')->label(__('Contact Phone'))->searchable(),
                IconColumn::make('is_pickup_point')->label(__('Pickup'))->boolean(),
                IconColumn::make('is_active')->label(__('Is Active'))->boolean(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
