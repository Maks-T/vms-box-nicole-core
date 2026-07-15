<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Warehouse;

class WarehouseFactory extends Factory
{
  protected $model = Warehouse::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->city();

    return [
      'slug' => Str::slug($name),
      'name' => [
        'ru' => 'Склад ' . $name,
        'en' => 'Warehouse ' . $name,
      ],
      'description' => [
        'ru' => 'Основной склад в городе ' . $name,
        'en' => 'Main warehouse in ' . $name,
      ],
      'address' => $this->faker->address(),
      'is_pickup_point' => true,
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}