<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;

class PricesRepeater
{
  public static function make(): Repeater
  {
    return Repeater::make('prices')
      ->label(__('Sales Pricing Matrix'))
      ->relationship('prices')
      ->required(fn (Get $get) => (bool) $get('is_manual_pricing'))
      ->minItems(fn (Get $get) => $get('is_manual_pricing') ? 1 : 0)
      ->visible(fn (Get $get) => (bool) $get('is_manual_pricing'))
      ->schema([
        Select::make('price_type_id')
          ->label(__('Price Type'))
          ->relationship('type', 'name')
          ->required()
          ->distinct()
          ->live()
          ->disableOptionsWhenSelectedInSiblingRepeaterItems()
          ->afterStateUpdated(function (Get $get, Set $set, $state) {
            if ($state) {
              $priceType = PriceType::with('currency')->find($state);
              $targetCurrency = $priceType?->currency?->code ?? 'RUB';
              $set('input_currency', $targetCurrency);

              $markup = (float) $get('markup_percent');
              \Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\PricingTab::updatePriceItem($get, $set, $targetCurrency, $markup);
            }
          }),

        Grid::make(3)
          ->schema([
            // Выбор валюты ввода
            ToggleButtons::make('input_currency')
              ->label(__('Input Currency'))
              ->options(function () {
                return Currency::where('is_active', true)
                  ->pluck('code', 'code')
                  ->toArray();
              })
              ->inline()
              ->live()
              ->dehydrated(false)
              ->disabled(fn (Get $get) => (float) $get('../../cost_price') <= 0)
              ->afterStateHydrated(function (ToggleButtons $component, Get $get, ?Model $record) {
                if (!$component->getState()) {
                  $priceType = PriceType::find($get('price_type_id'));
                  $component->state($priceType?->currency?->code ?? 'RUB');
                }
              })
              ->afterStateUpdated(function (Get $get, Set $set, $state) {
                $markup = (float) $get('markup_percent');
                \Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\PricingTab::updatePriceItem($get, $set, $state, $markup);
              }),

            // Ввод наценки в %
            TextInput::make('markup_percent')
              ->label(__('Markup (%)'))
              ->numeric()
              ->suffix('%')
              ->required()
              ->live(onBlur: true)
              ->disabled(fn (Get $get) => (float) $get('../../cost_price') <= 0)
              ->placeholder(fn (Get $get) => (float) $get('../../cost_price') <= 0 ? __('Locked') : '0')
              ->helperText(fn (Get $get) => (float) $get('../../cost_price') <= 0 ? __('Specify cost price first') : null)
              ->afterStateUpdated(function (Get $get, Set $set, $state) {
                $inputCurrency = $get('input_currency') ?? 'RUB';
                \Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\PricingTab::updatePriceItem($get, $set, $inputCurrency, (float) $state);
              }),

            // Итоговая расчетная цена
            TextInput::make('desired_price')
              ->label(__('Final Price'))
              ->numeric()
              ->live(onBlur: true)
              ->disabled(fn (Get $get) => (float) $get('../../cost_price') <= 0)
              ->placeholder(fn (Get $get) => (float) $get('../../cost_price') <= 0 ? __('Locked') : '0')
              ->dehydrated(false)
              ->afterStateHydrated(function (TextInput $component, Get $get, ?Model $record) {
                $markup = (float) $get('markup_percent');
                /** @var ProductVariant|null $variant */
                $variant = $record instanceof \Nicole\Box\Core\Models\ProductVariantPrice ? $record->variant : null;

                if ($variant) {
                  $priceType = PriceType::find($get('price_type_id'));
                  $calculated = app(PricingManager::class)->getVariantPrice($variant, $priceType?->slug ?? 'retail');
                  $component->state($calculated);
                } else {
                  $costPrice = (float) $get('../../cost_price');
                  $costCurrency = $get('../../currency') ?? 'USD';
                  $inputCurrency = $get('input_currency') ?? 'RUB';

                  if ($costPrice > 0) {
                    $priceInCostCurrency = $costPrice * (1 + $markup / 100);
                    $converted = app(PricingManager::class)->convert($priceInCostCurrency, $costCurrency, $inputCurrency);
                    $component->state(round($converted, 2));
                  }
                }
              })
              ->afterStateUpdated(function (Get $get, Set $set, $state) {
                $costPrice = (float) $get('../../cost_price');
                $costCurrency = $get('../../currency') ?? 'USD';
                $priceTypeId = $get('price_type_id');
                $desiredPrice = (float) $state;

                if ($desiredPrice <= 0) {
                  $set('markup_percent', 0.0000000000);
                  $set('desired_price', $costPrice);
                  return;
                }

                if ($costPrice <= 0) {
                  return;
                }

                $inputCurrency = $get('input_currency') ?? 'RUB';
                $priceInCostCurrency = app(PricingManager::class)->convert($desiredPrice, $inputCurrency, $costCurrency);
                $markup = (($priceInCostCurrency / $costPrice) - 1) * 100;

                $set('markup_percent', round($markup, 10));
              }),
          ]),
      ]);
  }

}
