<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;

class BaseCostSection
{
  public static function make(): Section
  {
    return Section::make(__('Base Cost (COGS)'))
      ->description(__('Physical purchasing cost and currency for this SKU.'))
      ->schema([
        Toggle::make('is_manual_pricing')
          ->label(__('Use customized prices'))
          ->helperText(__('Enable to set custom cost and individual selling prices instead of using the price group.'))
          ->live()
          ->columnSpanFull()
          ->default(false),

        Select::make('price_group_id')
          ->label(__('Price Group'))
          ->relationship('priceGroup', 'name', function ($query, Get $get, ?Model $record, Component $livewire) {
            $product = ProductVariantForm::resolveProduct($get, $record, $livewire);
            if ($product && $product->type) {
              return $query->where('product_family_id', $product->type->family_id);
            }
            return $query;
          })
          ->searchable()
          ->preload()
          ->live()
          ->required(fn(Get $get) => !$get('is_manual_pricing'))
          ->hidden(fn(Get $get) => (bool)$get('is_manual_pricing'))
          ->columnSpanFull(),

        Section::make(fn(Get $get) => __('Price Group Reference Information') . (filled($get('price_group_id')) ? ': ' . PriceGroup::find($get('price_group_id'))?->getTranslation('name', app()->getLocale()) : ''))
          ->visible(fn(Get $get) => !(bool)$get('is_manual_pricing') && filled($get('price_group_id')))
          ->collapsible()
          ->columnSpanFull()
          ->schema(function (Get $get) {
            $priceGroupId = $get('price_group_id');
            if (!$priceGroupId) return [];

            $priceGroup = PriceGroup::find($priceGroupId);
            if (!$priceGroup) return [];

            $pricingManager = app(PricingManager::class);
            $meta = $priceGroup->meta ?? [];
            $cost = (float)($meta['purchase_cost'] ?? 0);
            $purchaseCurrency = $meta['purchase_currency'] ?? 'USD';

            if ($cost <= 0) {
              return [
                TextEntry::make('no_cost')
                  ->hiddenLabel()
                  ->state(__('Base cost is not set in the selected price group.'))
              ];
            }

            $schema = [
              TextEntry::make('ref_base_cost')
                ->label(__('Base Cost'))
                ->state(number_format($cost, 2, '.', ' ') . " {$purchaseCurrency}")
                ->columnSpanFull(),
            ];

            foreach ($pricingManager->channelPriceTypes as $type) {
              $markup = (float)($meta["markup_{$type->slug}"] ?? 0);

              $targetCurrency = $type->currency->code ?? 'RUB';
              $convertedCost = $pricingManager->convert($cost, $purchaseCurrency, $targetCurrency);
              $finalPrice = $convertedCost * (1 + $markup / 100);

              $symbol = $type->currency->symbol
                ?? ($pricingManager->baseCurrency->symbol_native ?? ($pricingManager->baseCurrency->symbol ?? '₽'));

              $formattedPrice = number_format($finalPrice, 2, '.', ' ') . ' ' . $symbol;

              $schema[] = TextEntry::make("price_type_{$type->slug}")
                ->label((string)$type->getTranslation('name', app()->getLocale()))
                ->state("{$formattedPrice} (" . __('Markup') . ": {$markup}%)")
                ->columnSpan(1);
            }

            return $schema;
          })
          ->columns(2),

        TextInput::make('cost_price')
          ->label(__('Cost Price'))
          ->numeric()
          ->default(0)
          ->live(onBlur: true)
          ->required()
          ->visible(fn(Get $get) => (bool)$get('is_manual_pricing'))
          ->afterStateUpdated(function (Get $get, Set $set, $state) {
            $costPrice = (float)$state;
            $costCurrency = $get('currency') ?? 'USD';
            $prices = $get('prices') ?? [];

            foreach ($prices as $key => $priceData) {
              $priceTypeId = $priceData['price_type_id'] ?? null;
              $markup = (float)($priceData['markup_percent'] ?? 0);

              $inputCurrency = $priceData['input_currency'] ?? null;
              if (!$inputCurrency && $priceTypeId) {
                $priceType = PriceType::with('currency')->find($priceTypeId);
                $inputCurrency = $priceType?->currency?->code ?? 'RUB';
              }
              $inputCurrency ??= 'RUB';

              if ($costPrice > 0) {
                $priceInCostCurrency = $costPrice * (1 + $markup / 100);
                $converted = app(PricingManager::class)->convert($priceInCostCurrency, $costCurrency, $inputCurrency);
                $set("prices.{$key}.desired_price", round($converted, 2));
              }
            }
          }),

        Select::make('currency')
          ->label(__('Currency'))
          ->options(fn() => Currency::pluck('code', 'code')->toArray())
          ->default('RUB')
          ->live()
          ->required()
          ->visible(fn(Get $get) => (bool)$get('is_manual_pricing'))
          ->afterStateUpdated(function (Get $get, Set $set, $state) {
            $costPrice = (float)$get('cost_price');
            $costCurrency = $state ?? 'USD';
            $prices = $get('prices') ?? [];

            foreach ($prices as $key => $priceData) {
              $priceTypeId = $priceData['price_type_id'] ?? null;
              $markup = (float)($priceData['markup_percent'] ?? 0);

              $inputCurrency = $priceData['input_currency'] ?? null;
              if (!$inputCurrency && $priceTypeId) {
                $priceType = PriceType::with('currency')->find($priceTypeId);
                $inputCurrency = $priceType?->currency?->code ?? 'RUB';
              }
              $inputCurrency ??= 'RUB';

              if ($costPrice > 0) {
                $priceInCostCurrency = $costPrice * (1 + $markup / 100);
                $converted = app(PricingManager::class)->convert($priceInCostCurrency, $costCurrency, $inputCurrency);
                $set("prices.{$key}.desired_price", round($converted, 2));
              }
            }
          }),
      ])
      ->columns(2);
  }
}
