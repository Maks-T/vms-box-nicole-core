<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('id')
          ->label('№')
          ->sortable()
          ->weight('bold'),

        TextColumn::make('customer.full_name')
          ->label(__('Customer'))
          ->state(fn ($record) => $record->customer?->full_name ?? '-')
          ->searchable()
          ->sortable(['customers.last_name']),

        TextColumn::make('grand_total')
          ->label(__('Total'))
          ->money(fn ($record) => $record->currency)
          ->sortable()
          ->weight('bold')
          ->color('primary'),

        TextColumn::make('status.name')
          ->label(__('Status'))
          ->badge()

          ->color(fn ($record) => $record->status?->color ?? 'gray')
          ->sortable(),

        TextColumn::make('manager.name')
          ->label(__('Staff'))
          ->searchable()
          ->toggleable(),

        TextColumn::make('locale')
          ->label(__('Locale'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('created_at')
          ->label(__('Created At'))
          ->dateTime()
          ->sortable(),
      ])
      ->filters([

      ])
      ->recordActions([
        Action::make('open_in_calculator')
          ->label(__('Open in Calculator'))
          ->icon('heroicon-o-calculator')
          ->color('success')
          ->url(fn ($record): string => route('calculator.show', ['code' => $record->code]))
          ->openUrlInNewTab(),

        Action::make('view_html')
          ->label(__('View'))
          ->icon('heroicon-o-eye')
          ->color('gray')
          ->url(fn ($record): string => "/api/v1/orders/{$record->code}/html")
          ->openUrlInNewTab(),


        Action::make('print_pdf')
          ->label(__('PDF'))
          ->icon('heroicon-o-document-text')
          ->color('gray')
          ->url(fn ($record): string => "/api/v1/orders/{$record->code}/pdf")
          ->openUrlInNewTab(),

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
