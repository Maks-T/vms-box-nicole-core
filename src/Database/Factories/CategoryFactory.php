<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Category;

class CategoryFactory extends Factory
{

  protected $model = Category::class;

  public function definition(): array
  {
    $nameWord = $this->faker->unique()->word();

    return [

      'name' => [
        'ru' => 'Категория ' . ucfirst($nameWord),
        'en' => 'Category ' . ucfirst($nameWord),
      ],
      'slug' => Str::slug($nameWord),
      'description' => [
        'ru' => 'Описание для категории ' . $nameWord,
        'en' => 'Description for category ' . $nameWord,
      ],
      'is_active' => true,
      'sort_order' => 0,

    ];
  }
}
