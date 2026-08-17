<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Nicole\Box\Core\Contracts\PipelineScenarioCompilerInterface;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\PipelineScenario;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

class PipelineImporter implements ImportModuleInterface
{
  /**
   * Универсальная кэш-карта всех сущностей в памяти: [entity_type => [external_code => id]]
   * @var array<string, array<string, int>>
   */
  protected array $entityMaps = [];

  public function getName(): string
  {
    return 'Universal Pipelines & Scenarios (Core)';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $command->info('Start of importing universal selection rules (Nicole Core)...');

    $pipelinesData = $data['pipelines'] ?? [];
    $scenariosData = $data['pipeline_scenarios'] ?? [];
    $rulesData = $data['binding_rules'] ?? [];

    if (empty($pipelinesData)) {
      $command->warn('Skipped: The pipelines section is missing in import_data.json.');
      return;
    }

    // Импорт контейнеров пайплайнов
    $command->line('Importing pipeline containers...');
    $pipelineIdMap = [];
    $bar = $command->getOutput()->createProgressBar(count($pipelinesData));

    foreach ($pipelinesData as $plData) {
      $pipeline = Pipeline::updateOrCreate(
        ['code' => $plData['code']],
        [
          'slug' => $plData['slug'] ?? Str::slug((string)$plData['code']),
          'external_code' => $plData['external_code'] ?? null,
          'name' => $plData['name'],
          'description' => $plData['description'] ?? null,
          'schema' => $plData['schema'] ?? null,
          'is_active' => (bool)($plData['is_active'] ?? true),
          'sort_order' => (int)($plData['sort_order'] ?? 0),
        ]
      );

      if (!empty($plData['external_code'])) {
        $pipelineIdMap[$plData['external_code']] = $pipeline->id;
      }
      $bar->advance();
    }
    $bar->finish();
    $command->newLine();

    // Импорт сценариев подбора (pipeline_scenarios)
    if (!empty($scenariosData)) {
      $command->line('Importing selection scenarios...');
      $scenariosBar = $command->getOutput()->createProgressBar(count($scenariosData));

      $compiler = app()->bound(PipelineScenarioCompilerInterface::class)
        ? app(PipelineScenarioCompilerInterface::class)
        : null;

      foreach ($scenariosData as $scenData) {
        $pipelineId = null;
        if (!empty($scenData['pipeline_external_code'])) {
          $pipelineId = $pipelineIdMap[$scenData['pipeline_external_code']] ?? null;
        }

        if (!$pipelineId) {
          $scenariosBar->advance();
          continue;
        }

        $uiState = $scenData['ui_state'] ?? [];
        $translatedUiState = $this->translateUiStateRecursive($uiState);

        $scenario = PipelineScenario::updateOrCreate(
          [
            'pipeline_id' => $pipelineId,
            'code' => $scenData['code'],
          ],
          [
            'external_code' => $scenData['external_code'] ?? null,
            'name' => $scenData['name'],
            'description' => $scenData['description'] ?? null,
            'ui_state' => $translatedUiState,
            'is_active' => (bool)($scenData['is_active'] ?? true),
            'sort_order' => (int)($scenData['sort_order'] ?? 0),
          ]
        );

        if ($compiler) {
          $compiler->compile($scenario);
        }

        $scenariosBar->advance();
      }
      $scenariosBar->finish();
      $command->newLine();
    }

    // Импорт статических правил связей (binding_rules)
    if (!empty($rulesData)) {
      $command->line('Importing static link rules...');
      $rulesBar = $command->getOutput()->createProgressBar(count($rulesData));

      foreach ($rulesData as $ruleData) {
        $pipelineId = null;
        if (!empty($ruleData['pipeline_external_code'])) {
          $pipelineId = $pipelineIdMap[$ruleData['pipeline_external_code']] ?? null;
        }

        $parentType = $this->resolveMorphClass($ruleData['parent_type_key'] ?? '');
        $parentId = $this->resolveModelId($ruleData['parent_type_key'] ?? '', $ruleData['parent_external_code'] ?? '');

        $childType = null;
        $childId = null;
        if (!empty($ruleData['child_type_key']) && !empty($ruleData['child_external_code'])) {
          $childType = $this->resolveMorphClass($ruleData['child_type_key']);
          $childId = $this->resolveModelId($ruleData['child_type_key'], $ruleData['child_external_code']);
        }

        if (!$parentId) {
          $rulesBar->advance();
          continue;
        }

        $translatedConditions = $this->translateConditions($ruleData['conditions'] ?? []);

        BindingRule::updateOrCreate(
          ['external_code' => $ruleData['external_code']],
          [
            'pipeline_id' => $pipelineId,
            'name' => $ruleData['name'] ?? 'BOM Link',
            'role' => $ruleData['role'] ?? null,
            'parent_type' => $parentType,
            'parent_id' => $parentId,
            'child_type' => $childType,
            'child_id' => $childId,
            'conditions' => $translatedConditions,
            'static_meta' => $ruleData['static_meta'] ?? null,
            'quantity_formula' => (string)($ruleData['quantity_formula'] ?? '1'),
            'is_required' => (bool)($ruleData['is_required'] ?? false),
            'sort_order' => (int)($ruleData['sort_order'] ?? 0),
          ]
        );

        $rulesBar->advance();
      }
      $rulesBar->finish();
      $command->newLine();
    }

    $command->info('The import of universal rules has been successfully completed.');
  }

  /**
   * Универсальный резолвинг морф-типа сущности через Relation::morphMap.
   */
  protected function resolveMorphClass(string $key): string
  {
    $modelClass = Relation::getMorphedModel($key);

    if ($modelClass && class_exists($modelClass)) {
      return (new $modelClass())->getMorphClass();
    }

    return (new Product())->getMorphClass();
  }

  /**
   * Универсальный поиск ID записи в памяти с ленивым авто-прогревом
   */
  protected function resolveModelId(string $key, string $extCode): ?int
  {
    if (empty($key) || empty($extCode)) {
      return null;
    }

    // если карта для этой сущности еще не в памяти, загружаем ее одним запросом
    if (!isset($this->entityMaps[$key])) {
      $modelClass = Relation::getMorphedModel($key);

      if ($modelClass && class_exists($modelClass)) {
        $this->entityMaps[$key] = $modelClass::query()
          ->whereNotNull('external_code')
          ->pluck('id', 'external_code')
          ->toArray();
      } else {
        $this->entityMaps[$key] = [];
      }
    }

    return $this->entityMaps[$key][$extCode] ?? null;
  }

  /**
   * Рекурсивный перевод всех external_code из ui_state сценария в числовые ID базы данных
   */
  protected function translateUiStateRecursive(mixed $obj): mixed
  {
    if (is_array($obj)) {
      foreach ($obj as $k => $v) {
        $obj[$k] = $this->translateUiStateRecursive($v);
      }
      return $obj;
    }

    if (is_string($obj)) {
      // Ищем соответствие в товарах, опциях атрибутов или типах товаров
      $id = $this->resolveModelId(ET::PRODUCT, $obj)
        ?? ($this->resolveModelId(ET::ATTRIBUTE_OPTION, $obj)
          ?? $this->resolveModelId(ET::PRODUCT_TYPE, $obj));

      if ($id !== null) {
        return (int) $id;
      }
    }

    return $obj;
  }

  protected function translateConditions(array $conditions): array
  {
    if (empty($conditions['and'])) {
      return $conditions;
    }

    $translatedAnd = [];
    foreach ($conditions['and'] as $cond) {
      if (str_starts_with($cond['var'] ?? '', 'parent.') && is_array($cond['val'] ?? null)) {
        $translatedVals = [];
        foreach ($cond['val'] as $val) {
          $optionId = $this->resolveModelId('attribute_option', (string)$val);
          $translatedVals[] = $optionId ?? $val;
        }
        $cond['val'] = $translatedVals;
      }
      $translatedAnd[] = $cond;
    }

    return ['and' => $translatedAnd];
  }

}
