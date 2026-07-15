<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;

class ProductTypeFactory extends Factory
{
  protected $model = ProductType::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'code' => Str::slug($name, '_'),
      'slug' => Str::slug($name, '-'),
      'family_id' => ProductFamily::factory(),
      'name' => [
        'ru' => 'Тип ' . ucfirst($name),
        'en' => 'Type ' . ucfirst($name),
      ],
      'is_active' => true,
      'sort_order' => 0,
    ];
  }
}
