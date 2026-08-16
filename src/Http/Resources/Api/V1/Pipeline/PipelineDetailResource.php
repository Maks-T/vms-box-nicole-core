<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

/**
 * Метаданные и схема ролей пайплайна.
 *
 * @mixin \Nicole\Box\Core\Models\Pipeline
 */
class PipelineDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $treeService = app(PipelineTreeService::class);

    return [
      /**
       * Системный ID пайплайна.
       * @var int
       * @example 1
       */
      'id' => (int)$this->id,

      /**
       * Уникальный системный код пайплайна.
       * @var string
       * @example "pl_terrace"
       */
      'code' => $this->code,

      /**
       * ЧПУ-слаг пайплайна.
       * @var string|null
       * @example "terrace"
       */
      'slug' => $this->slug,

      /**
       * Отображаемое название калькулятора.
       * @var string
       * @example "Конфигуратор террасного настила (ДПК)"
       */
      'name' => (string)$this->name,

      /**
       * Описание назначения пайплайна.
       * @var string|null
       * @example "Цепочка связей для расчета террасных систем"
       */
      'description' => $this->description ? (string)$this->description : null,

      /**
       * Скомпилированная схема ролей, слотов и типов товаров.
       * @var array<string, array<string, array{label_key: string, type_code: string, is_required: bool, is_multiple: bool}>>
       * @example {"terraceBoard": {"baseClip": {"label_key": "Рядовой кляймер", "type_code": "brackets", "is_required": true, "is_multiple": false}}}
       */
      'schema' => $treeService->getPipelineSchema($this->code, $this->resource),
    ];
  }
}
