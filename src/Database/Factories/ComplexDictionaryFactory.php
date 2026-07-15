<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\ComplexDictionary;

class ComplexDictionaryFactory extends Factory
{
  protected $model = ComplexDictionary::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'code' => Str::slug($name, '_'),
      'name' => [
        'ru' => 'Умный справочник ' . ucfirst($name),
        'en' => 'Complex Dictionary ' . ucfirst($name),
      ],
      'meta_schema' => [],
      'is_active' => true,
      'sort_order' => 0,
    ];
  }

}
