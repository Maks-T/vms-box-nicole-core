<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;

class ProductVariantsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        ImageColumn::make('preview_image')
          ->label(__('Photo'))
          ->state(function (ProductVariant $record) {
            $url = $record->getPreviewUrl();
            if (!$url) {
              return null;
            }

            return str_starts_with($url, 'http') ? $url : url($url);
          })
          ->circular(),

        TextColumn::make('sku')
          ->label(__('SKU'))
          ->searchable(['sku', 'external_code'])
          ->sortable()
          ->copyable()
          ->fontFamily('mono'),

        TextColumn::make('product.name')
          ->label(__('Parent Product'))
          ->searchable()
          ->sortable()
          ->wrap()
          ->toggleable(),

        TextColumn::make('cost_price')
          ->label(__('Cost Price'))
          ->state(fn(ProductVariant $record) => app(PricingManager::class)->getVariantCostPrice($record))
          ->money(fn(ProductVariant $record) => app(PricingManager::class)->getVariantCostCurrency($record))
          ->sortable(),

        TextColumn::make('retail_price')
          ->label(__('Retail Price'))
          ->state(
            fn(ProductVariant $record): float => app(
              PricingManager::class,
            )->getVariantPrice($record),
          )
          ->money(fn() => app(PricingManager::class)->baseCurrency->code)
          ->sortable(false),

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

        IconColumn::make('is_active')
          ->label(__('Status'))
          ->boolean()
          ->toggleable(),
      ])
      ->filtersFormWidth(Width::TwoExtraLarge)
      ->filtersFormColumns(2)
      ->filters([
        // Фильтр по категории родительского товара
        SelectFilter::make('category_id')
          ->label(__('Category'))
          ->relationship('product.category', 'name')
          ->multiple()
          ->searchable()
          ->preload(),

        // Фильтр по типу родительского товара
        SelectFilter::make('product_type_id')
          ->label(__('Product Type Schema'))
          ->relationship('product.type', 'name')
          ->multiple()
          ->searchable()
          ->preload(),

        // Главный вариант или второстепенный
        TernaryFilter::make('is_default')
          ->label(__('Variant Type'))
          ->placeholder(__('All'))
          ->trueLabel(__('Default only'))
          ->falseLabel(__('Secondary only'))
          ->native(false),

        // Активность
        TernaryFilter::make('is_active')->label(__('Is active'))->native(false),

        // Наличие фото
        TernaryFilter::make('has_images')
          ->label(__('Images Presence'))
          ->placeholder(__('All'))
          ->trueLabel(__('With photos only'))
          ->falseLabel(__('Without photos'))
          ->queries(
            true: fn(Builder $query) => $query->whereHas('media'),
            false: fn(Builder $query) => $query->whereDoesntHave('media'),
            blank: fn(Builder $query) => $query,
          )
          ->native(false),

        // Диапазон цен
        Filter::make('cost_price')
          ->columnSpanFull()
          ->schema([
            Grid::make(2)->schema([
              TextInput::make('price_from')
                ->label(__('Cost Price From'))
                ->numeric(),
              TextInput::make('price_to')
                ->label(__('Cost Price To'))
                ->numeric(),
            ]),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                filled($data['price_from']),
                fn($q) => $q->where('cost_price', '>=', $data['price_from']),
              )
              ->when(
                filled($data['price_to']),
                fn($q) => $q->where('cost_price', '<=', $data['price_to']),
              );
          }),

        // Диапазон остатков
        Filter::make('stock')
          ->columnSpanFull()
          ->schema([
            Grid::make(2)->schema([
              TextInput::make('stock_from')->label(__('Stock From'))->numeric(),
              TextInput::make('stock_to')->label(__('Stock To'))->numeric(),
            ]),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                filled($data['stock_from']),
                fn($q) => $q->where('stock', '>=', $data['stock_from']),
              )
              ->when(
                filled($data['stock_to']),
                fn($q) => $q->where('stock', '<=', $data['stock_to']),
              );
          }),
      ])
      ->recordActions([EditAction::make()])
      ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
      ->defaultSort('updated_at', 'desc')
      // сохранять примененные фильтры в сессии
      ->persistFiltersInSession()
      // сохранять строку глобального поиска таблицы
      ->persistSearchInSession()
      // сохранять выбранную сортировку колонок
      ->persistSortInSession();
  }
}
