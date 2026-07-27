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
       * Информация о пайплайне и его схема.
       */
      'pipeline' => new PipelineDetailResource($this->resource['pipeline_model']),

      /**
       * Компактная карта связей и параметров (ключ - роль).
       * @var object
       * @example {"corner": 180, "baseClip": {"variant_id": 4, "params": {"holes": 1}}, "startClip": {"variant_id": 8, "params": {"holes": 1}}, "stepBoards": [{"variant_id": 119, "params": {"noseSize": 20}}]}
       */
      'bindings' => (object) ($this->resource['bindings'] ?? []),

      /**
       * Подробный дерево-граф связей для визуального конструктора.
       */
      'tree' => new PipelineTreeResource($this->resource['tree']),
    ];
  }
}
