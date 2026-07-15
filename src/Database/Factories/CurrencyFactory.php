<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\Currency;

class CurrencyFactory extends Factory
{
  protected $model = Currency::class;

  public function definition(): array
  {
    $code = $this->faker->unique()->currencyCode();

    return [
      'code' => $code,
      'name' => [
        'ru' => 'Валюта ' . $code,
        'en' => 'Currency ' . $code,
      ],
      'symbol' => '$',
      'rate' => $this->faker->randomFloat(4, 1, 100),
      'is_default' => false,
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}