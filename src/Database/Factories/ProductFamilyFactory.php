<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\ProductFamily;

class ProductFamilyFactory extends Factory
{
  protected $model = ProductFamily::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'code' => Str::slug($name, '_'),
      'slug' => Str::slug($name, '-'),
      'name' => [
        'ru' => 'Семейство ' . ucfirst($name),
        'en' => 'Family ' . ucfirst($name),
      ],
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}
