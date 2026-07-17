<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Services\PricingManager;

class PriceGroupsTable
{
  public static function configure(Table $table): Table
  {
    $priceTypes = PriceType::with('currency')->get();

    $columns = [
      TextColumn::make('name')
        ->label(__('Name'))
        ->searchable()
        ->sortable()
        ->weight('bold'),

      TextColumn::make('slug')
        ->label(__('Slug'))
        ->fontFamily('mono')
        ->color('gray')
        ->toggleable(isToggledHiddenByDefault: true),

      TextColumn::make('family.name')
        ->label(__('Family'))
        ->badge()
        ->color('gray')
        ->sortable(),

      TextColumn::make('meta.purchase_cost')
        ->label(__('Base Cost'))
        ->state(function (PriceGroup $record) {
          $cost = (float)($record->meta['purchase_cost'] ?? 0);
          $currency = $record->meta['purchase_currency'] ?? 'USD';
          return number_format($cost, 2, '.', ' ') . ' ' . $currency;
        })
        ->sortable(),
    ];

    // Динамическая генерация колонок наценки и итоговой цены в своей валюте для каждого типа цены
    foreach ($priceTypes as $type) {
      $isRetail = $type->slug === 'retail';

      $columns[] = TextColumn::make("meta.markup_{$type->slug}")
        ->label(__('Markup :type', ['type' => (string) $type->name]))
        ->suffix('%')
        ->color('gray')
        ->sortable()
        ->toggleable(isToggledHiddenByDefault: !$isRetail);

      $columns[] = TextColumn::make("calculated_total_{$type->slug}")
        ->label(__('Total :type', ['type' => (string) $type->name]))
        ->state(function (PriceGroup $record) use ($type) {
          $meta = $record->meta ?? [];
          $cost = (float) ($meta['purchase_cost'] ?? 0);
          $fromCurrency = $meta['purchase_currency'] ?? 'USD';
          $markup = (float) ($meta["markup_{$type->slug}"] ?? 0);

          if ($cost <= 0) {
            return '-';
          }

          $targetCurrency = $type->currency->code ?? 'RUB';
          $convertedCost = app(PricingManager::class)->convert($cost, $fromCurrency, $targetCurrency);
          $total = $convertedCost * (1 + $markup / 100);

          $pricingManager = app(PricingManager::class);
          $symbol = $type->currency->symbol
            ?? ($pricingManager->baseCurrency->symbol_native ?? ($pricingManager->baseCurrency->symbol ?? '₽'));

          return number_format($total, 2, '.', ' ') . ' ' . $symbol;
        })
        ->color('success')
        ->weight('bold')
        ->alignEnd()
        ->toggleable(isToggledHiddenByDefault: !$isRetail);
    }

    $columns[] = IconColumn::make('is_active')->label(__('Is Active'))->boolean();

    return $table
      ->columns($columns)
      ->filters([
        SelectFilter::make('product_family_id')
          ->label(__('Family'))
          ->relationship('family', 'name')
          ->preload(),

        FilterHelper::activeFilter(),
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->recordActions([
        EditAction::make(),
        ProtectDefaultRecord::tableDeleteAction('Cannot delete default record'),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          ProtectDefaultRecord::tableBulkDeleteAction('Default records skipped'),
        ]),
      ]);
  }
}
