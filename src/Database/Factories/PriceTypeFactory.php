<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;

class PriceTypeFactory extends Factory
{
  protected $model = PriceType::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'slug' => Str::slug($name),
      'name' => [
        'ru' => 'Тип цены ' . ucfirst($name),
        'en' => 'Price Type ' . ucfirst($name),
      ],
      'is_default' => false,
      'currency_id' => Currency::factory(), // Связываем с валютой
      'sort_order' => 0,
    ];
  }

}
