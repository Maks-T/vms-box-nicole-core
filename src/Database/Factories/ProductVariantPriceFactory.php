<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;

class ProductVariantPriceFactory extends Factory
{
  protected $model = ProductVariantPrice::class;

  public function definition(): array
  {
    return [
      'product_variant_id' => ProductVariant::factory(),
      'price_type_id' => PriceType::factory(),
      'markup_percent' => $this->faker->randomFloat(4, 5, 50),
    ];
  }

}
