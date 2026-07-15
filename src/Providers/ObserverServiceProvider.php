<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Observers\CatalogObserver;

class ObserverServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Product::observe(CatalogObserver::class);
    ProductVariant::observe(CatalogObserver::class);
    ProductVariantPrice::observe(CatalogObserver::class);
    Currency::observe(CatalogObserver::class);
    ComplexDictionaryRecord::observe(CatalogObserver::class);
  }
}
