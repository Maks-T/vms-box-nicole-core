<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Notifications\Notification;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Filters\ProductVariantFilters;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Filament\Helpers\TableHelper;

class ProductVariantsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TableHelper::idColumn(),

        ImageColumn::make('preview_image')
          ->label(__('Photo'))
          ->state(function (ProductVariant $record) {
            $url = $record->getPreviewUrl();
            if (!$url) {
              return null;
            }
            return str_starts_with($url, 'http') ? $url : url($url);
          })
          ->circular()
          ->toggleable(),

        TextColumn::make('sku')
          ->label(__('SKU'))
          ->searchable(['sku', 'external_code'])
          ->sortable()
          ->copyable()
          ->fontFamily('mono')
          ->toggleable(),

        TextColumn::make('product.name')
          ->label(__('Parent Product'))
          ->state(function (ProductVariant $record) {
            return $record->product?->getTranslation('name', app()->getLocale())
              ?? ($record->product?->getTranslation('name', 'ru') ?? '-');
          })
          ->url(fn(ProductVariant $record) => $record->product_id
            ? \Nicole\Box\Core\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $record->product_id])
            : null
          )
          ->openUrlInNewTab()
          ->searchable()
          ->sortable()
          ->wrap()
          ->toggleable(),

        TableHelper::externalCodeColumn(),

        TextColumn::make('price_group.name')
          ->label(__('Price Group'))
          ->state(fn(ProductVariant $record) => $record->priceGroup?->getTranslation('name', app()->getLocale())
            ?? ($record->priceGroup?->getTranslation('name', 'ru') ?? '-')
          )
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true)
          ->sortable(),

        TextInputColumn::make('cost_price')
          ->label(__('Cost Price'))
          ->type('number')
          ->step('any')
          ->suffix(fn(ProductVariant $record) => ' ' . app(PricingManager::class)->getVariantCostCurrency($record))
          ->disabled(fn(ProductVariant $record) => !$record->is_manual_pricing)
          ->updateStateUsing(function (ProductVariant $record, $state) {
            try {
              $record->update(['cost_price' => (float)$state]);
              $record->product?->refreshMinPrice();
              Notification::make()
                ->success()
                ->title('Себестоимость сохранена')
                ->send();
            } catch (\Throwable $e) {
              Notification::make()
                ->danger()
                ->title('Ошибка изменения себестоимости')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            }
          })
          ->width('150px')
          ->toggleable()
          ->alignEnd(),

        TextInputColumn::make('markup_percent')
          ->label(__('Markup (%)'))
          ->state(function (ProductVariant $record) {
            $retailPriceTypeId = \Nicole\Box\Core\Models\PriceType::where('slug', 'retail')->value('id') ?? 1;
            $priceRecord = $record->prices()->where('price_type_id', $retailPriceTypeId)->first();
            return $priceRecord?->markup_percent ?? 0.0;
          })
          ->disabled(fn(ProductVariant $record) => !$record->is_manual_pricing)
          ->updateStateUsing(function (ProductVariant $record, $state) {
            try {
              $retailPriceTypeId = \Nicole\Box\Core\Models\PriceType::where('slug', 'retail')->value('id') ?? 1;
              \Nicole\Box\Core\Models\ProductVariantPrice::updateOrCreate(
                ['product_variant_id' => $record->id, 'price_type_id' => $retailPriceTypeId],
                ['markup_percent' => (float)$state]
              );
              $record->product?->refreshMinPrice();
              Notification::make()
                ->success()
                ->title('Процент наценки сохранен')
                ->send();
            } catch (\Throwable $e) {
              Notification::make()
                ->danger()
                ->title('Ошибка изменения наценки')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            }
          })
          ->type('number')
          ->step('any')
          ->suffix('%')
          ->width('130px')
          ->toggleable()
          ->alignEnd(),

        TableHelper::codeColumn('currency'),

        TextColumn::make('retail_price')
          ->label(__('Retail Price'))
          ->state(
            fn(ProductVariant $record): float => app(
              PricingManager::class,
            )->getVariantPrice($record),
          )
          ->money(fn() => app(PricingManager::class)->baseCurrency->code)
          ->sortable(false)
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('stock')
          ->label(__('Stock'))
          ->numeric()
          ->sortable()
          ->badge()
          ->state(
            fn(ProductVariant $record) => $record->product?->catalog_type ===
            'service'
              ? null
              : $record->stock,
          )
          ->formatStateUsing(fn($state) => $state === null ? '-' : $state)
          ->color(
            fn(?float $state): string => match (true) {
              $state === null => 'gray',
              $state <= 0 => 'danger',
              $state < 10 => 'warning',
              default => 'success',
            },
          )
          ->toggleable(),

        IconColumn::make('is_default')
          ->label(__('Default'))
          ->boolean()
          ->toggleable(),

        IconColumn::make('is_manual_pricing')
          ->label(__('Manual Pricing'))
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),

        TableHelper::statusColumn(),

        TableHelper::sortOrderColumn(),
        TableHelper::createdAtColumn(),
        TableHelper::updatedAtColumn(),
      ])
      ->columnManagerColumns(2)
      ->filtersLayout(FiltersLayout::AboveContent)
      ->filtersFormColumns(3)
      ->filters(ProductVariantFilters::all())
      ->recordActions([EditAction::make()])
      ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
      ->defaultSort('updated_at', 'desc')
      ->persistFiltersInSession()
      ->persistSearchInSession()
      ->persistSortInSession();
  }
}
