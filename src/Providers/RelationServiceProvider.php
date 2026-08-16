<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\Customer;
use Nicole\Box\Core\Models\Media;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Models\OrderSection;
use Nicole\Box\Core\Models\OrderStatus;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Stock;
use Nicole\Box\Core\Models\Unit;
use Nicole\Box\Core\Models\Warehouse;

use Nicole\Box\Core\Support\Constants\EntityType as ET;

class RelationServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Relation::morphMap([
      ET::PRODUCT                   => Product::class,
      ET::PRODUCT_VARIANT           => ProductVariant::class,
      ET::CATEGORY                  => Category::class,
      ET::WAREHOUSE                 => Warehouse::class,
      ET::ATTRIBUTE                 => Attribute::class,
      ET::MEDIA                     => Media::class,
      ET::PRODUCT_TYPE              => ProductType::class,
      ET::PIPELINE                  => Pipeline::class,
      ET::FAMILY                    => ProductFamily::class,
      ET::COMPLEX_DICTIONARY        => ComplexDictionary::class,
      ET::COMPLEX_DICTIONARY_RECORD => ComplexDictionaryRecord::class,
      ET::PRICE_GROUP               => PriceGroup::class,
      ET::PRICE_TYPE                => PriceType::class,
      ET::CURRENCY                  => Currency::class,
      ET::UNIT                      => Unit::class,
      ET::ATTRIBUTE_OPTION          => AttributeOption::class,
      ET::STOCK                     => Stock::class,
      ET::ORDER                     => Order::class,
      ET::ORDER_SECTION             => OrderSection::class,
      ET::ORDER_STATUS              => OrderStatus::class,
      ET::CUSTOMER                  => Customer::class,
    ]);
  }
}
