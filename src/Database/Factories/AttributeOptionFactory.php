<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;

class AttributeOptionFactory extends Factory
{
  protected $model = AttributeOption::class;

  public function definition(): array
  {
    $value = $this->faker->unique()->word();

    return [
      'attribute_id' => Attribute::factory()->dictionary(), // Связываем со справочным атрибутом
      'slug' => Str::slug($value),
      'value' => [
        'ru' => ucfirst($value),
        'en' => ucfirst($value),
      ],
      'meta' => null,
      'sort_order' => 0,
    ];
  }

}
