<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Models\OrderStatus;

class OrdersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('id')
          ->label('ID')
          ->sortable()
          ->weight('bold'),

        TextColumn::make('code')
          ->label('№')
          ->searchable()
          ->sortable()
          ->weight('bold'),

        // @since 2026-09-06: Название проекта / расчета
        TextColumn::make('name')
          ->label(__('Project Name'))
          ->state(fn ($record) => $record->name ?: ($record->title ?: ($record->calc_state['project']['name'] ?? '-')))
          ->searchable()
          ->sortable()
          ->weight('medium'),

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

        SelectColumn::make('status_id')
          ->label(__('Status'))
          ->options(function () {
            $locale = app()->getLocale();
            return OrderStatus::query()
              ->where('is_active', true)
              ->orderBy('sort_order')
              ->get()
              ->mapWithKeys(fn (OrderStatus $status) => [
                $status->id => $status->getTranslation('name', $locale) ?: (string) $status->name,
              ])
              ->toArray();
          })
          ->selectablePlaceholder(false)
          ->sortable(),

        SelectColumn::make('manager_id')
          ->label(__('Staff'))
          ->options(function () {
            $userModel = config('nicole.models.staff', \App\Models\User::class);
            return $userModel::query()
              ->orderBy('name')
              ->pluck('name', 'id')
              ->toArray();
          })
          ->placeholder(__('Not assigned'))
          ->searchable()
          ->sortable()
          ->toggleable(),

        TextColumn::make('locale')
          ->label(__('Locale'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('created_at')
          ->label(__('Created At'))
          ->dateTime('d.m.Y H:i')
          ->timezone(config('app.timezone', 'Asia/Yekaterinburg'))
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