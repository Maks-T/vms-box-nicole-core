<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Конфигурация калькулятора, карта связей и дерево элементов.
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
       * @example {"corner": {"type": "product_variant", "id": 183, "parent_id": 89}, "baseClip": {"type": "product_variant", "id": 5, "parent_id": 14, "params": {"holes": "1"}}, "startClip": {"type": "product_variant", "id": 9, "parent_id": 16, "params": {"holes": "1"}}, "stepBoards": [{"type": "product_variant", "id": 110, "parent_id": 67, "params": {"noseSize": "20"}}]}
       */
      'bindings' => (object)($this->resource['bindings'] ?? []),

      /**
       * Подробный дерево-граф связей для визуального конструктора.
       */
      'tree' => new PipelineTreeResource($this->resource['tree']),
    ];
  }
}
