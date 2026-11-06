<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Filters\ProductVariantFilters;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Catalog\ReplicationService;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Support\CatalogCache;


class ProductVariantsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      // @since 2026-11-06: eager loading живёт в таблице,
      // чтобы его получал и VariantsRelationManager внутри карточки товара
      ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => static::applyEagerLoads($query))
      ->deferLoading()
      // @since 2026-11-06: 10 строк по умолчанию — время рендера таблицы растёт линейно от числа строк
      ->defaultPaginationPageOption(10)
      ->paginationPageOptions([10, 25, 50, 100])
      ->columns([
        TableHelper::idColumn(),

        ImageColumn::make('preview_image')
          ->label(__('Photo'))
          ->state(function (ProductVariant $record) {
            // media и product.media подгружены в applyEagerLoads()
            // @since 2026-11-06: миниатюра 96×96 вместо оригинала
            $url = $record->getThumbUrl();
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

        TextColumn::make('name')
          ->label(__('Variant Name'))
          ->state(function (ProductVariant $record) {
            $locale = app()->getLocale();
            return $record->getTranslation('name', $locale)
              ?: ($record->getTranslation('name', 'ru') ?: '—');
          })
          ->searchable(query: function (\Illuminate\Database\Eloquent\Builder $query, string $search): \Illuminate\Database\Eloquent\Builder {
            return $query->where('name->ru', 'ilike', "%{$search}%")
              ->orWhere('name->en', 'ilike', "%{$search}%");
          })
          ->sortable()
          ->weight('medium')
          ->wrap()
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

        ...static::buildDynamicAttributeColumns(),

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
                ->title(__('Cost price saved'))
                ->send();
            } catch (\Throwable $e) {
              Notification::make()
                ->danger()
                ->title(__('Error changing cost price'))
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
            // @since 2026-11-06: id типа цены - 1 раз за запрос, prices - eager
            $retailPriceTypeId = static::retailPriceTypeId();
            $priceRecord = $record->prices->firstWhere('price_type_id', $retailPriceTypeId);
            return $priceRecord?->markup_percent ?? 0.0;
          })
          ->disabled(fn(ProductVariant $record) => !$record->is_manual_pricing)
          ->updateStateUsing(function (ProductVariant $record, $state) {
            try {
              $retailPriceTypeId = static::retailPriceTypeId();
              \Nicole\Box\Core\Models\ProductVariantPrice::updateOrCreate(
                ['product_variant_id' => $record->id, 'price_type_id' => $retailPriceTypeId],
                ['markup_percent' => (float)$state]
              );
              $record->product?->refreshMinPrice();
              Notification::make()
                ->success()
                ->title(__('Markup percentage saved'))
                ->send();
            } catch (\Throwable $e) {
              Notification::make()
                ->danger()
                ->title(__('Error changing markup'))
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
      ->recordActions([
        ActionGroup::make([
          EditAction::make(),
          ReplicateAction::make()
            ->label(__('Replicate'))
            ->modalHeading(__('Replicate Variant'))
            ->schema([
              TextInput::make('sku')
                ->label(__('SKU / Article'))
                ->required()
                ->default(fn (ProductVariant $record, ReplicationService $service): string => $service->generateUniqueVariantSku((string) $record->sku))
                ->maxLength(255),
              TextInput::make('name')
                ->label(__('Variant Name'))
                ->placeholder(fn (ProductVariant $record): ?string => $record->getTranslation('name', app()->getLocale(), false)),
            ])
            ->action(function (ProductVariant $record, array $data, ReplicationService $service): void {
              $overrides = ['sku' => $data['sku']];
              if (!empty($data['name'])) {
                $overrides['name'] = [app()->getLocale() => $data['name']];
              }
              $replica = $service->replicateVariant($record, $overrides);

              Notification::make()
                ->success()
                ->title(__('Variant duplicated: :sku', ['sku' => $replica->sku]))
                ->send();
            }),
          DeleteAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          BulkAction::make('replicate')
            ->label(__('Replicate'))
            ->icon('heroicon-o-document-duplicate')
            ->requiresConfirmation()
            ->modalHeading(__('Replicate selected variants'))
            ->action(function (Collection $records, ReplicationService $service): void {
              $count = 0;
              foreach ($records as $record) {
                if ($record instanceof ProductVariant) {
                  $service->replicateVariant($record);
                  $count++;
                }
              }

              Notification::make()
                ->success()
                ->title(__('Successfully duplicated :count variants', ['count' => $count]))
                ->send();
            })
            ->deselectRecordsAfterCompletion(),
          DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('updated_at', 'desc')
      ->persistFiltersInSession()
      ->persistSearchInSession()
      ->persistSortInSession();
  }

  /**
   * Единый набор связей для списка модификаций (ресурс + relation manager товара).
   *
   * @since 2026-11-06
   */
  public static function applyEagerLoads(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
  {
    return $query->with([
      'media',
      'product.media',
      'product.category',
      'product.unit',
      'product.attributeValues.option',
      'product.attributeValues.complexRecord',
      'attributeValues.option',
      'attributeValues.complexRecord',
      'priceGroup',
      'prices',
    ]);
  }

  /**
   * ID розничного типа цены - один запрос на HTTP-запрос.
   *
   * @since 2026-11-06
   */
  protected static function retailPriceTypeId(): int
  {
    return once(fn(): int => (int)(\Nicole\Box\Core\Models\PriceType::where('slug', 'retail')->value('id') ?? 1));
  }

  /**
   * Динамическое формирование колонок EAV-атрибутов с флагом show_in_variant_grid.
   *
   * @return array<\Filament\Tables\Columns\Column>
   * @since 2026-09-29
   */
  protected static function buildDynamicAttributeColumns(): array
  {
    // @since 2026-11-06: Таблица создаётся миграцией ядра.
    $user = auth()->user();
    $attributes = once(fn() => Attribute::query()
      ->whereHas('uiSetting', fn($q) => $q->where('show_in_variant_grid', true))
      ->with(['uiSetting', 'options'])
      ->get()
      ->sortBy(fn($attr) => $attr->uiSetting?->grid_sort_order ?? 0));

    $columns = [];

    foreach ($attributes as $attribute) {
      $uiSetting = $attribute->uiSetting;
      if (!$uiSetting || !$uiSetting->canViewByUser($user)) {
        continue;
      }

      $colName = "dyn_attr_{$attribute->code}";
      $label = (string) ($attribute->getTranslation('name', app()->getLocale()) ?? $attribute->name);
      $isInline = $uiSetting->is_inline_editable && $uiSetting->canEditByUser($user);

      if ($isInline && $attribute->type === Attribute::TYPE_DICTIONARY) {
        $options = $attribute->options->pluck('value', 'id')->toArray();
        $columns[] = SelectColumn::make($colName)
          ->label($label)
          ->options($options)
          ->state(fn(ProductVariant $record) => ($record->attributeValues->firstWhere('attribute_id', $attribute->id)
            ?? $record->product?->attributeValues->firstWhere('attribute_id', $attribute->id))?->value_option_id
          )
          ->updateStateUsing(function (ProductVariant $record, $state) use ($attribute) {
            ProductAttributeValue::updateOrCreate(
              ['attributable_id' => $record->id, 'attributable_type' => $record->getMorphClass(), 'attribute_id' => $attribute->id],
              ['value_option_id' => $state ? (int)$state : null]
            );
            CatalogCache::invalidate();
          })
          ->toggleable();
        continue;
      }

      if ($isInline && $attribute->type === Attribute::TYPE_BOOLEAN) {
        $columns[] = ToggleColumn::make($colName)
          ->label($label)
          ->state(fn(ProductVariant $record) => (bool) $record->attributeValues->firstWhere('attribute_id', $attribute->id)?->value_boolean)
          ->updateStateUsing(function (ProductVariant $record, $state) use ($attribute) {
            ProductAttributeValue::updateOrCreate(
              ['attributable_id' => $record->id, 'attributable_type' => $record->getMorphClass(), 'attribute_id' => $attribute->id],
              ['value_boolean' => (bool)$state]
            );
            CatalogCache::invalidate();
          })
          ->toggleable();
        continue;
      }

      $col = TextColumn::make($colName)
        ->label($label)
        ->state(function (ProductVariant $record) use ($attribute) {
          $val = $record->attributeValues->firstWhere('attribute_id', $attribute->id)
            ?? $record->product?->attributeValues->firstWhere('attribute_id', $attribute->id);
          if (!$val) return null;
          if ($val->option) return $val->option->getTranslation('value', app()->getLocale()) ?? $val->option->value;
          if ($val->complexRecord) return $val->complexRecord->getTranslation('name', app()->getLocale()) ?? $val->complexRecord->name;
          if ($val->value_boolean !== null) return $val->value_boolean ? __('Yes') : __('No');
          return $val->value_string ?? (string)$val->value_numeric;
        })
        ->formatStateUsing(function (ProductVariant $record, $state) use ($attribute, $uiSetting) {
          if (!$state) return '—';
          $val = $record->attributeValues->firstWhere('attribute_id', $attribute->id)
            ?? $record->product?->attributeValues->firstWhere('attribute_id', $attribute->id);
          $hex = $val?->option?->meta['hex'] ?? null;
          if ($hex && ($uiSetting->grid_component_type === 'color' || str_contains($attribute->code, 'color'))) {
            return new HtmlString("<div style='display:inline-flex;align-items:center;gap:6px;'><span style='width:12px;height:12px;border-radius:9999px;background:{$hex};border:1px solid rgba(0,0,0,0.15);'></span><span>{$state}</span></div>");
          }
          return $state;
        })
        ->toggleable();

      $columns[] = $col;
    }

    return $columns;
  }
}
