<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductAttributeValue;

class ProductAttributeValueFactory extends Factory
{
  protected $model = ProductAttributeValue::class;

  public function definition(): array
  {
    $product = Product::factory();

    return [
      'attribute_id' => Attribute::factory(), // Создает базовый атрибут
      'attributable_id' => $product,
      'attributable_type' => (new Product())->getMorphClass(), // Полиморфная связь "product"
      'value_string' => $this->faker->word(),
      'value_numeric' => null,
      'value_boolean' => null,
      'value_option_id' => null,
      'value_complex_id' => null,
      'value_entity_id' => null,
    ];
  }

}
