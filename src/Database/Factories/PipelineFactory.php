<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\Pipeline;

class PipelineFactory extends Factory
{
  protected $model = Pipeline::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'name' => [
        'ru' => 'Конвейер ' . $name,
        'en' => 'Pipeline ' . $name,
      ],
      'industry' => 'stone',
      'description' => [
        'ru' => 'Описание конвейера ' . $name,
        'en' => 'Description for ' . $name,
      ],
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}
