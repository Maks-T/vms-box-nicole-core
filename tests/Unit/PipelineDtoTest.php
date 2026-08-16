<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\DTO\Pipeline\PipelineItemDto;
use Nicole\Box\Core\DTO\Pipeline\PipelineInputDto;
use Nicole\Box\Core\DTO\Pipeline\PipelineExportDto;
use Nicole\Box\Core\DTO\Pipeline\BindingRuleExportDto;
use Nicole\Box\Core\DTO\Pipeline\EntityReferenceDto;

class PipelineDtoTest extends TestCase
{
  use LazilyRefreshDatabase;

  /**
   * Проверка инициализации входных полиморфных DTO.
   */
  public function test_can_instantiate_pipeline_input_dtos(): void
  {
    $item = new PipelineItemDto(
      id: 10,
      quantity: 2.5,
      type: 'product_variant',
      parentNodeId: 5
    );

    $this->assertEquals(10, $item->id);
    $this->assertEquals('product_variant', $item->type);
    $this->assertEquals(2.5, $item->quantity);
    $this->assertEquals(5, $item->parentNodeId);

    $input = new PipelineInputDto(
      items: [$item],
      context: ['thickness' => 20]
    );

    $this->assertCount(1, $input->items);
    $this->assertEquals($item, $input->items[0]);
    $this->assertEquals(20, $input->context['thickness']);
  }

  /**
   * Проверка экспорта конвейера и правил через DTO.
   */
  public function test_can_map_pipeline_models_to_export_dtos(): void
  {
    /** @var Pipeline $pipeline */
    $pipeline = Pipeline::factory()->create([
      'code' => 'pl_stone',
    ]);

    /** @var BindingRule $rule */
    $rule = BindingRule::factory()->create([
      'pipeline_id' => $pipeline->id,
      'quantity_formula' => 'width * 2',
      'is_required' => true,
    ]);

    $dto = PipelineExportDto::fromModel($pipeline);

    $this->assertEquals($pipeline->id, $dto->id);
    $this->assertEquals($pipeline->code, $dto->code);
    $this->assertEquals($pipeline->getTranslation('name', 'ru'), $dto->name);
    $this->assertCount(1, $dto->rules);

    /** @var BindingRuleExportDto $ruleDto */
    $ruleDto = $dto->rules->first();
    $this->assertEquals($rule->id, $ruleDto->id);
    $this->assertEquals($rule->parent_type, $ruleDto->parentType);
    $this->assertEquals($rule->parent_id, $ruleDto->parentId);
    $this->assertEquals($rule->child_id, $ruleDto->childId);
    $this->assertEquals('width * 2', $ruleDto->quantityFormula);
    $this->assertTrue($ruleDto->isRequired);
  }
}
