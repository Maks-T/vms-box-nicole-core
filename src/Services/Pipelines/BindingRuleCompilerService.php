<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Pipelines;

use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\PipelineScenario;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductType;

class BindingRuleCompilerService
{
    /**
     * Компиляция ui_state сценария в плоские записи binding_rules
     */
    public function compile(PipelineScenario $scenario): void
    {
        // 1. Очищаем старые скомпилированные правила этого сценария
        BindingRule::where('scenario_id', $scenario->id)->delete();

        $uiState = $scenario->ui_state ?? [];
        $pipelineId = $scenario->pipeline_id;

        $groups = $uiState['groups'] ?? [];
        $resolutions = $uiState['resolutions'] ?? [];
        $rangeFrom = (int) ($uiState['range_from'] ?? 1);
        $rangeTo = (int) ($uiState['range_to'] ?? 999);

        // Гарантируем чистый сплошной JSON-массив через array_values()
        $conditionsList = array_values(array_filter([
            !empty($groups) ? ['var' => 'parent.camera_groups', 'op' => 'in', 'val' => $groups] : null,
            !empty($resolutions) ? ['var' => 'parent.camera_resolution', 'op' => 'in', 'val' => $resolutions] : null,
            ['var' => 'context.total_cameras', 'op' => '>=', 'val' => $rangeFrom],
            ['var' => 'context.total_cameras', 'op' => '<=', 'val' => $rangeTo],
        ]));

        $baseConditions = ['and' => $conditionsList];
        $parentCameraTypeId = ProductType::where('code', 'camera')->value('id') ?? 1;
        $sortOrder = 10;

        // 2. Компиляция правил коммутаторов (Switches)
        foreach ($uiState['switches'] ?? [] as $switch) {
            if (empty($switch['product_id'])) continue;

            BindingRule::create([
                'external_code' => 'rule_' . md5($scenario->id . '_switch_' . $switch['product_id']),
                'pipeline_id' => $pipelineId,
                'scenario_id' => $scenario->id,
                'name' => __('Auto Switch'),
                'role' => 'switch',
                'parent_type' => (new ProductType())->getMorphClass(),
                'parent_id' => $parentCameraTypeId,
                'child_type' => (new Product())->getMorphClass(),
                'child_id' => (int) $switch['product_id'],
                'conditions' => $baseConditions,
                'quantity_formula' => (string) ($switch['quantity'] ?? '1'),
                'sort_order' => $sortOrder++,
            ]);
        }

        // 3. Компиляция правил хранения (NVR + HDD) по дням
        foreach ($uiState['storage'] ?? [] as $days => $storageData) {
            $daysInt = (int) $days;
            $storageConditions = $baseConditions;
            $storageConditions['and'][] = ['var' => 'context.storage_days', 'op' => '==', 'val' => $daysInt];

            // NVR Видеорегистратор
            if (!empty($storageData['product_id'])) {
                BindingRule::create([
                    'external_code' => 'rule_' . md5($scenario->id . '_recorder_' . $storageData['product_id'] . '_' . $daysInt),
                    'pipeline_id' => $pipelineId,
                    'scenario_id' => $scenario->id,
                    'name' => __('NVR for :days days', ['days' => $daysInt]),
                    'role' => 'recorder',
                    'parent_type' => (new ProductType())->getMorphClass(),
                    'parent_id' => $parentCameraTypeId,
                    'child_type' => (new Product())->getMorphClass(),
                    'child_id' => (int) $storageData['product_id'],
                    'conditions' => $storageConditions,
                    'quantity_formula' => '1',
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Накопители HDD
            foreach ($storageData['memory'] ?? [] as $memory) {
                if (empty($memory['product_id'])) continue;

                BindingRule::create([
                    'external_code' => 'rule_' . md5($scenario->id . '_storage_' . $memory['product_id'] . '_' . $daysInt),
                    'pipeline_id' => $pipelineId,
                    'scenario_id' => $scenario->id,
                    'name' => __('HDD for :days days', ['days' => $daysInt]),
                    'role' => 'storage',
                    'parent_type' => (new ProductType())->getMorphClass(),
                    'parent_id' => $parentCameraTypeId,
                    'child_type' => (new Product())->getMorphClass(),
                    'child_id' => (int) $memory['product_id'],
                    'conditions' => $storageConditions,
                    'quantity_formula' => (string) ($memory['quantity'] ?? '1'),
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}
