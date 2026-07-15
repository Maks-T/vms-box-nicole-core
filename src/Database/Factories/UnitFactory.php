<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\Unit;

class UnitFactory extends Factory
{
  protected $model = Unit::class;

  public function definition(): array
  {
    $unitName = $this->faker->unique()->word();

    return [
      'slug' => $unitName,
      'code' => $this->faker->numerify('###'),
      'name' => [
        'ru' => 'Штука ' . $unitName,
        'en' => 'Piece ' . $unitName,
      ],
      'symbol' => [
        'ru' => 'шт.',
        'en' => 'pcs',
      ],
      'sort_order' => 0,
    ];
  }
}
