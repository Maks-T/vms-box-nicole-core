<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\PipelineScenario;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Pipelines\BindingRuleCompilerService;

class PipelineImporter implements ImportModuleInterface
{
  protected array $productMap = [];
  protected array $optionMap = [];
  protected array $typeMap = [];

  public function getName(): string
  {
    return 'Universal Pipelines & Scenarios (Core)';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $command->info('Старт импорта универсальных правил подбора ( Nicole Core )...');

    $pipelinesData = $data['pipelines'] ?? [];
    $scenariosData = $data['pipeline_scenarios'] ?? [];
    $rulesData = $data['binding_rules'] ?? [];

    if (empty($pipelinesData)) {
      $command->warn('  ⚠ Пропущено: Раздел pipelines отсутствует в import_data.json.');
      return;
    }

    $this->loadSystemMaps();

    // 1. Импорт контейнеров пайплайнов
    $command->line('Импорт контейнеров пайплайнов...');
    $pipelineIdMap = [];
    $bar = $command->getOutput()->createProgressBar(count($pipelinesData));

    foreach ($pipelinesData as $plData) {
      $pipeline = Pipeline::updateOrCreate(
        ['code' => $plData['code']],
        [
          'slug' => $plData['slug'] ?? Str::slug($plData['code']),
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

    // 2. Импорт сценариев подбора (pipeline_scenarios)
    if (!empty($scenariosData)) {
      $command->line('Импорт сценариев и автоматическая компиляция правил...');
      $scenariosBar = $command->getOutput()->createProgressBar(count($scenariosData));
      $compiler = app(BindingRuleCompilerService::class);

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

        // Автоматическая компиляция ui_state в плоские binding_rules
        $compiler->compile($scenario);

        $scenariosBar->advance();
      }
      $scenariosBar->finish();
      $command->newLine();
    }

    // 3. Импорт статических правил связей (binding_rules)
    if (!empty($rulesData)) {
      $command->line('Импорт статических правил связей...');
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

    $command->info('Импорт универсальных правил успешно завершен.');
  }

  protected function loadSystemMaps(): void
  {
    $this->productMap = Product::pluck('id', 'external_code')->toArray();
    $this->optionMap = AttributeOption::pluck('id', 'external_code')->toArray();
    $this->typeMap = ProductType::pluck('id', 'external_code')->toArray();
  }

  protected function translateUiStateRecursive(mixed $obj): mixed
  {
    if (is_array($obj)) {
      foreach ($obj as $k => $v) {
        $obj[$k] = $this->translateUiStateRecursive($v);
      }
      return $obj;
    }

    if (is_string($obj)) {
      if (isset($this->productMap[$obj])) {
        return $this->productMap[$obj];
      }
      if (isset($this->optionMap[$obj])) {
        return $this->optionMap[$obj];
      }
      if (isset($this->typeMap[$obj])) {
        return $this->typeMap[$obj];
      }
    }

    return $obj;
  }

  protected function resolveMorphClass(string $key): string
  {
    return match ($key) {
      'product_type' => (new ProductType())->getMorphClass(),
      'product' => (new Product())->getMorphClass(),
      'variant' => (new ProductVariant())->getMorphClass(),
      default => (new Product())->getMorphClass(),
    };
  }

  protected function resolveModelId(string $key, string $extCode): ?int
  {
    return match ($key) {
      'product_type' => $this->typeMap[$extCode] ?? null,
      'product' => $this->productMap[$extCode] ?? null,
      'variant' => ProductVariant::where('external_code', $extCode)->value('id'),
      default => null,
    };
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
          if (isset($this->optionMap[$val])) {
            $translatedVals[] = $this->optionMap[$val];
          }
        }
        $cond['val'] = $translatedVals;
      }
      $translatedAnd[] = $cond;
    }

    return ['and' => $translatedAnd];
  }
}
