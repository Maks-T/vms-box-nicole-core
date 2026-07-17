<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Tables;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Services\PricingManager;

class ProductsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TableHelper::photoColumn(), // Shared

        TextColumn::make('name')
          ->label(__('Name'))
          ->searchable(['name', 'slug', 'external_code'])
          ->sortable()
          ->wrap(),

        TextColumn::make('type.name')
          ->label(__('Product Type'))
          ->badge()
          ->toggleable(),

        TextColumn::make('catalog_type')
          ->label(__('Kind'))
          ->badge()
          ->color('gray'),

        TextColumn::make('min_price')
          ->label(__('Price From'))
          ->money(fn() => app(PricingManager::class)->baseCurrency->code)
          ->sortable(),

        TableHelper::statusColumn(), // Shared

        TextColumn::make('variants_count')
          ->label(__('SKUs'))
          ->counts('variants')
          ->badge()
          ->color('info'),
      ])
      ->filtersFormWidth(Width::TwoExtraLarge)
      ->filtersFormColumns(2)
      ->filters([
        // Фильтр по семейству (Камень, Мойки и т.д.)
        FilterHelper::selectFilter(
          'family_id',
          __('Product Family'),
          fn () => ProductFamily::pluck('name', 'id')->toArray(),
          function (Builder $query, array $data) {
            $value = $data['family_id'] ?? null;

            if (! empty($value)) {
              $query->whereHas(
                'type.family',
                fn ($f) => $f->whereIn('id', (array) $value),
              );
            }

            return $query;
          },
        ),

        // Фильтр по типу товара
        SelectFilter::make('product_type_id')
          ->label(__('Product Type'))
          ->relationship('type', 'name')
          ->multiple()
          ->searchable()
          ->preload(),

        // Фильтр по категории
        SelectFilter::make('category_id')
          ->label(__('Category'))
          ->relationship('category', 'name')
          ->multiple()
          ->searchable()
          ->preload(),

        // Активность
        FilterHelper::activeFilter(), // Shared

        // Наличие фото
        TernaryFilter::make('has_images')
          ->label(__('Images Presence'))
          ->placeholder(__('All'))
          ->trueLabel(__('With photos only'))
          ->falseLabel(__('Without photos'))
          ->queries(
          // Ищем товары со своими фото или товары, у которых есть активные вариации с фото
            true: fn (Builder $query) => $query->where(function ($q) {
              $q->whereHas('media')
                ->orWhereHas('variants', function ($vQ) {
                  $vQ->where('is_active', true)
                    ->whereHas('media');
                });
            }),
            // Ищем товары без своих фото и у которых нет активных вариаций с фото
            false: fn (Builder $query) => $query->whereDoesntHave('media')
              ->whereDoesntHave('variants', function ($vQ) {
                $vQ->where('is_active', true)
                  ->whereHas('media');
              }),
            blank: fn (Builder $query) => $query,
          )
          ->native(false),

        // Диапазон цен
        Filter::make('min_price')
          ->columnSpanFull()
          ->schema([
            Grid::make(2)->schema([
              TextInput::make('price_from')
                ->label(__('Price From'))
                ->numeric(),
              TextInput::make('price_to')
                ->label(__('Price To'))
                ->numeric(),
            ]),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                filled($data['price_from']),
                fn ($q) => $q->where('min_price', '>=', $data['price_from']),
              )
              ->when(
                filled($data['price_to']),
                fn ($q) => $q->where('min_price', '<=', $data['price_to']),
              );
          }),
      ])
      // сохранять примененные фильтры в сессии
      ->persistFiltersInSession()
      // сохранять строку глобального поиска таблицы
      ->persistSearchInSession()
      // сохранять выбранную сортировку колонок
      ->persistSortInSession();
  }
}
