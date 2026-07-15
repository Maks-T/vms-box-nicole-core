<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\Unit;
use Nicole\Box\Core\Support\Constants\CatalogType;

class ProductFactory extends Factory
{
  protected $model = Product::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'catalog_type' => CatalogType::PRODUCT,
      'product_type_id' => ProductType::factory(),
      'category_id' => Category::factory(),
      'unit_id' => Unit::factory(),
      'name' => [
        'ru' => 'Камень ' . ucfirst($name),
        'en' => 'Stone ' . ucfirst($name),
      ],
      'slug' => Str::slug($name),
      'description' => [
        'ru' => $this->faker->paragraph(),
        'en' => $this->faker->paragraph(),
      ],
      'min_price' => 0.0,
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

  /**
   * Кастомное состояние: создание услуги вместо физического товара.
   */
  public function service(): self
  {
    return $this->state(fn (array $attributes) => [
      'catalog_type' => CatalogType::SERVICE,
      'name' => [
        'ru' => 'Услуга обработки',
        'en' => 'Processing service',
      ],
    ]);
  }
}
