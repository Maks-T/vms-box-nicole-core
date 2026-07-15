<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Attribute;

class AttributeFactory extends Factory
{
  protected $model = Attribute::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'code' => Str::slug($name, '_'),
      'type' => Attribute::TYPE_STRING, // по умолчанию строка
      'name' => [
        'ru' => 'Свойство ' . ucfirst($name),
        'en' => 'Property ' . ucfirst($name),
      ],
      'unit_id' => null,
      'complex_dictionary_id' => null,
      'is_multiple' => false,
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

  /**
   * Состояние для создания справочного атрибута (Select)
   */
  public function dictionary(): self
  {
    return $this->state(fn (array $attributes) => [
      'type' => Attribute::TYPE_DICTIONARY,
    ]);
  }
}
