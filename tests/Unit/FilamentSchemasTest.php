<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Unit;

use Tests\TestCase;
use Nicole\Box\Core\Filament\Schemas\TaxRegionsSchema;
use Nicole\Box\Core\Filament\Schemas\BrandColorSchema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;

class FilamentSchemasTest extends TestCase
{
  /**
   * Сценарий: Проверка корректной инициализации схемы налоговых регионов.
   */
  public function test_can_instantiate_tax_regions_schema(): void
  {
    $schema = TaxRegionsSchema::make();

    $this->assertInstanceOf(Repeater::class, $schema);
  }

  /**
   * Сценарий: Проверка корректной инициализации выбора цвета бренда.
   */
  public function test_can_instantiate_brand_color_schema(): void
  {
    $schema = BrandColorSchema::make();

    $this->assertInstanceOf(Select::class, $schema);
  }

}