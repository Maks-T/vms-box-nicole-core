<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

class ProductVariantFactory extends Factory
{
  protected $model = ProductVariant::class;

  public function definition(): array
  {
    $sku = 'sku_' . $this->faker->unique()->slug(2);

    return [
      'product_id' => Product::factory(), // Привязываем к родителю
      'sku' => $sku,
      'cost_price' => $this->faker->randomFloat(2, 1000, 5000),
      'currency' => 'RUB',
      'stock' => 10.0,
      'is_default' => false,
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}
