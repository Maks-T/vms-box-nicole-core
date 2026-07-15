<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\Product;

class BindingRuleFactory extends Factory
{
  protected $model = BindingRule::class;

  public function definition(): array
  {
    $productParent = Product::factory();
    $productChild = Product::factory();

    return [
      'pipeline_id' => Pipeline::factory(),
      'name' => 'Правило привязки элементов',
      'parent_type' => (new Product())->getMorphClass(),
      'parent_id' => $productParent,
      'child_type' => (new Product())->getMorphClass(),
      'child_id' => $productChild,
      'conditions' => [],
      'quantity_formula' => '1',
      'is_required' => false,
      'sort_order' => 0,
    ];
  }

}
