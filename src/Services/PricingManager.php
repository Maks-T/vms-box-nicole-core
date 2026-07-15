<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services;

use Illuminate\Support\Collection;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

class PricingManager
{
  private Collection $priceTypes;

  public Currency $baseCurrency {
    get => $this->baseCurrency ??= Currency::where('is_default', true)->first()
      ?? throw new \RuntimeException(__('Critical error: Base currency (is_default = true) is not set in the system. Please check currency settings.'));
  }

  public PriceType $defaultPriceType {
    get => $this->defaultPriceType ??= PriceType::where('is_default', true)->first()
      ?? throw new \RuntimeException(__('Critical error: Base price type (is_default = true) is not set in the system.'));
  }

  public Collection $currenciesList {
    get => $this->currenciesList ??= Currency::where('is_active', true)->get();
  }

  public Collection $channelPriceTypes {
    get {
      if (!isset($this->channelPriceTypes)) {
        $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
        $this->channelPriceTypes = PriceType::publicInChannel($channel)->orderBy('sort_order')->get();
      }
      return $this->channelPriceTypes;
    }
  }

  private function getPriceTypeBySlug(string $slug): \Illuminate\Database\Eloquent\Model
  {
    if (!isset($this->priceTypes)) {
      $this->priceTypes = PriceType::with('currency')->get()->keyBy('slug');
    }

    return $this->priceTypes->get($slug) ?? $this->defaultPriceType;
  }

  public function convert(float $amount, string $fromCode, string $toCode): float
  {
    if ($amount <= 0 || $fromCode === $toCode) {
      return $amount;
    }

    $currencies = $this->currenciesList->keyBy('code');
    $fromRate = $currencies->get($fromCode)?->rate ?? 1.0;
    $toRate = $currencies->get($toCode)?->rate ?? 1.0;

    $baseAmount = $amount * $fromRate;
    return $baseAmount / $toRate;
  }

  public function getVariantPrice(ProductVariant $variant, ?string $priceTypeSlug = null): float
  {
    $priceTypeSlug = $priceTypeSlug ?? $this->defaultPriceType->slug;
    /** @var PriceType $priceType */
    $priceType = $this->getPriceTypeBySlug($priceTypeSlug);

    $product = $variant->product;

    // Ручные наценки (только если включен ручной режим или у товара нет ценовой группы)
    if ($variant->is_manual_pricing || !$variant->price_group_id) {
      if ($variant->relationLoaded('prices')) {
        $priceRecord = $variant->prices->first(function ($price) use ($priceTypeSlug) {
          return $price->type && $price->type->slug === $priceTypeSlug;
        });
      } else {
        $priceRecord = $variant->prices()
          ->whereHas('type', fn($q) => $q->where('slug', $priceTypeSlug))
          ->first();
      }

      if ($priceRecord) {
        $costPrice = (float)$variant->cost_price;
        $costCurrency = $variant->currency;
        $markup = (float)$priceRecord->markup_percent;

        if ($costPrice > 0) {
          $priceInCostCurrency = $costPrice * (1 + $markup / 100);
          $targetCurrency = $priceRecord->type->currency->code ?? $this->baseCurrency->code;

          return round($this->convert($priceInCostCurrency, $costCurrency, $targetCurrency), 2);
        }
      }
    }

    // Расчет по выделенной ценовой группе (если ручной режим выключен и группа привязана)
    if (!$variant->is_manual_pricing && $variant->price_group_id && $variant->priceGroup) {
      $calculatedPrice = $this->calculatePriceGroupPrice(
        $variant->priceGroup,
        'purchase_cost',
        $priceType
      );

      if ($calculatedPrice !== null) {
        return $calculatedPrice;
      }
    }

    return 0.0;
  }

  private function calculatePriceGroupPrice(PriceGroup $priceGroup, string $field, PriceType $priceType): ?float
  {
    $meta = $priceGroup->meta ?? [];
    $cost = (float)($meta[$field] ?? 0);

    if ($cost <= 0) {
      return null;
    }

    $markupKey = $field . '_markup_' . $priceType->slug;
    $markup = (float)($meta[$markupKey] ?? ($meta[$field . '_markup'] ?? 0));

    $baseCurrencyCode = $this->baseCurrency->code;
    $currencyCode = $meta['purchase_currency'] ?? 'USD';

    $targetCurrencyCode = $priceType->currency->code ?? $baseCurrencyCode;
    $convertedCost = $this->convert($cost, $currencyCode, $targetCurrencyCode);

    return round($convertedCost * (1 + $markup / 100), 2);
  }

  public function getVariantPricesMap(ProductVariant $variant): array
  {
    $prices = [];
    foreach ($this->channelPriceTypes as $type) {
      $prices[$type->slug] = $this->getVariantPrice($variant, $type->slug);
    }

    return $prices;
  }

  public function getRetailPrice(Product $product): float
  {
    return (float)$product->min_price;
  }

  /**
   * Расчетная базовая себестоимость вариации с учетом выделенной ценовой группы.
   */
  public function getVariantCostPrice(ProductVariant $variant): float
  {
    if ($variant->is_manual_pricing || !$variant->price_group_id) {
      return (float)$variant->cost_price;
    }

    if ($variant->priceGroup) {
      $meta = $variant->priceGroup->meta ?? [];
      return (float)($meta['purchase_cost'] ?? 0.0);
    }

    return (float)$variant->cost_price;
  }

  /**
   * Валюта базовой себестоимости вариации с учетом справочников.
   */
  public function getVariantCostCurrency(ProductVariant $variant): string
  {
    if ($variant->is_manual_pricing || !$variant->price_group_id) {
      return $variant->currency;
    }

    if ($variant->priceGroup) {
      return $variant->priceGroup->meta['purchase_currency'] ?? 'USD';
    }

    return $variant->currency;
  }

}
