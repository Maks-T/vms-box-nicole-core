<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Contracts;

use Nicole\Box\Core\Models\PipelineScenario;

interface PipelineScenarioCompilerInterface
{
  /**
   * Скомпилировать ui_state сценария в плоские правила binding_rules.
   */
  public function compile(PipelineScenario $scenario): void;
}
