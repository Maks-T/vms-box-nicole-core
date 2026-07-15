<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\OrderProduct;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Filament\Resources\ProductVariants\ProductVariantResource;
use Filament\Actions\Action;

class ProductsRelationManager extends RelationManager
{
  protected static string $relationship = 'products';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Catalog Products');
  }

  public function table(Table $table): Table
  {

    $getVariantUrl = fn (OrderProduct $record) => $record->product_variant_id
      ? ProductVariantResource::getUrl('edit', ['record' => $record->product_variant_id])
      : null;

    return $table
      ->recordTitleAttribute('variant.sku')
      ->modifyQueryUsing(fn (Builder $query) => $query->with([
        'variant.product.unit',
        'order',
        'section'
      ]))
      ->columns([

        ImageColumn::make('variant.preview_image')
          ->label(__('Photo'))
          ->state(function (OrderProduct $record) {
            return $record->variant?->getPreviewUrl() ?? null;
          })
          ->circular()
          ->imageWidth(60)
          ->imageHeight(60)
          ->url($getVariantUrl)
          ->openUrlInNewTab(),

        TextColumn::make('variant.product.name')
          ->label(__('Name'))
          ->state(fn (OrderProduct $record) => $record->variant?->product?->name ?? '-')
          ->weight('bold')
          ->searchable()
          ->url($getVariantUrl)
          ->openUrlInNewTab(),

        TextColumn::make('variant.sku')
          ->label(__('Code'))
          ->fontFamily('mono')
          ->color('gray')
          ->searchable()
          ->url($getVariantUrl)
          ->openUrlInNewTab(),

        TextColumn::make('price')
          ->label(__('Price'))
          ->state(function (OrderProduct $record) {
            if (!$record->variant) return 0;
            return app(PricingManager::class)->getVariantPrice($record->variant);
          })
          ->money(fn () => app(PricingManager::class)->baseCurrency->code)
          ->alignEnd(),

        TextColumn::make('quantity')
          ->label(__('Qty'))
          ->state(fn (OrderProduct $record) => (float)$record->quantity)
          ->weight('bold')
          ->color('primary')
          ->alignEnd(),

        TextColumn::make('unit')
          ->label(__('Unit'))
          ->state(function (OrderProduct $record) {
            $unit = $record->variant?->product?->unit;
            return $unit
              ? ($unit->getTranslation('symbol', app()->getLocale()) ?? $unit->symbol)
              : 'шт.';
          })
          ->badge()
          ->color('gray')
          ->alignCenter(),

        TextColumn::make('row_total')
          ->label(__('Total'))
          ->state(function (OrderProduct $record) {
            if (!$record->variant) return 0;
            $price = app(PricingManager::class)->getVariantPrice($record->variant);
            return $price * (float)$record->quantity;
          })
          ->money(fn (OrderProduct $record) => $record->order?->currency ?? 'RUB')
          ->weight('bold')
          ->color('success')
          ->alignEnd(),

        TextColumn::make('section.title')
          ->label(__('Order Section'))
          ->state(fn (OrderProduct $record) => $record->section?->title ?? '-')
          ->color('gray')
          ->toggleable(),
      ])

      ->recordActions([
        Action::make('open_variant')
          ->label(__('Open'))
          ->icon('heroicon-o-arrow-top-right-on-square')
          ->color('info')
          ->url($getVariantUrl)
          ->openUrlInNewTab(),
      ])
      ->filters([
      ])
      ->toolbarActions([
      ])
      ->defaultSort('id', 'asc');
  }
}
