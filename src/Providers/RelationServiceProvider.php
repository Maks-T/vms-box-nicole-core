<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class RelationServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Relation::morphMap([
      'product' => \Nicole\Box\Core\Models\Product::class,
      'product_variant' => \Nicole\Box\Core\Models\ProductVariant::class,
      'category' => \Nicole\Box\Core\Models\Category::class,
      'warehouse' => \Nicole\Box\Core\Models\Warehouse::class,
      'attribute' => \Nicole\Box\Core\Models\Attribute::class,
      'media' => \Nicole\Box\Core\Models\Media::class,
      'product_type' => \Nicole\Box\Core\Models\ProductType::class,
      'pipeline' => \Nicole\Box\Core\Models\Pipeline::class,
      'family' => \Nicole\Box\Core\Models\ProductFamily::class,
      'complex_dictionary' => \Nicole\Box\Core\Models\ComplexDictionary::class,
      'complex_dictionary_record' => \Nicole\Box\Core\Models\ComplexDictionaryRecord::class,
      'price_type' => \Nicole\Box\Core\Models\PriceType::class,
      'currency' => \Nicole\Box\Core\Models\Currency::class,
      'unit' => \Nicole\Box\Core\Models\Unit::class,
      'attribute_option' => \Nicole\Box\Core\Models\AttributeOption::class,
      'stock' => \Nicole\Box\Core\Models\Stock::class,
      'order' => \Nicole\Box\Core\Models\Order::class,
      'order_section' => \Nicole\Box\Core\Models\OrderSection::class,
      'order_status' => \Nicole\Box\Core\Models\OrderStatus::class,
      'customer' => \Nicole\Box\Core\Models\Customer::class,
    ]);
  }
}
