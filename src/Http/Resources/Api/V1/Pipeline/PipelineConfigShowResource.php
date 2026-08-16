<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Полная конфигурация пайплайна: схема, карта связей (bindings) и дерево элементов (tree).
 */
class PipelineConfigShowResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Информация о пайплайне и его схема слотов.
       */
      'pipeline' => new PipelineDetailResource($this->resource['pipeline_model']),

      /**
       * Компактная карта связей и параметров (ключ - роль слота).
       * @var object
       * @example {
       *   "corner": {"type": "product_variant", "id": 180, "parent_id": 86},
       *   "baseClip": {"type": "product_variant", "id": 4, "parent_id": 4, "params": {"holes": "1"}},
       *   "startClip": {"type": "product_variant", "id": 8, "parent_id": 8, "params": {"holes": "1"}},
       *   "stepBoards": [{"type": "product_variant", "id": 119, "parent_id": 66, "params": {"noseSize": "20"}}],
       *   "universalBoards": [{"type": "product_variant", "id": 29, "parent_id": 28, "fixing": {"type": "product_variant", "id": 17, "parent_id": 17}}]
       * }
       */
      'bindings' => (object) ($this->resource['bindings'] ?? []),

      /**
       * Подробный дерево-граф связей для визуального конструктора.
       */
      'tree' => new PipelineTreeResource($this->resource['tree']),
    ];
  }
}
