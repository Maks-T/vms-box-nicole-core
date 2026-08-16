<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;

/**
 * @mixin \Nicole\Box\Core\Models\Pipeline
 */
class BootstrapPipelineResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $treeService = app(PipelineTreeService::class);

    return [
      /**
       * Внутренний ID пайплайна.
       * @var int
       * @example 1
       */
      'id' => $this->id,

      /**
       * Уникальный системный код пайплайна.
       * @var string
       * @example "pl_terrace"
       */
      'code' => $this->code,

      /**
       * ЧПУ-слаг пайплайна.
       * @var string|null
       * @example "pl-terrace"
       */
      'slug' => $this->slug,

      /**
       * Отображаемое название пайплайна.
       * @var string
       * @example "Конфигуратор террас"
       */
      'name' => (string) $this->name,

      /**
       * Описание назначения пайплайна.
       * @var string|null
       * @example "Цепочка связей для расчета террасных систем"
       */
      'description' => $this->description ? (string) $this->description : null,

      /**
       * Скомпилированная схема ролей, слотов и типов назначения пайплайна.
       * @var array<string, array<string, array{label_key: string, target_type: string, target_code: string|null, is_required: bool, is_multiple: bool}>>
       */
      'schema' => $treeService->getPipelineSchema($this->code, $this->resource),
    ];
  }
}
