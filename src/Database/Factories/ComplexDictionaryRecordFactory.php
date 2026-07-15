<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;

class ComplexDictionaryRecordFactory extends Factory
{
  protected $model = ComplexDictionaryRecord::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->word();

    return [
      'dictionary_id' => ComplexDictionary::factory(), // Связываем со справочником
      'slug' => Str::slug($name),
      'name' => [
        'ru' => 'Запись ' . ucfirst($name),
        'en' => 'Record ' . ucfirst($name),
      ],
      'meta' => [],
      'is_active' => true,
      'sort_order' => 0,
    ];
  }
}
