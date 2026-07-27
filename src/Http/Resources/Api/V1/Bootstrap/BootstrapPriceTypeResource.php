<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Nicole\Box\Core\Models\PriceType
 */
class BootstrapPriceTypeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Системный идентификатор типа цены (слаг).
       * @var string
       * @example "retail"
       */
      'slug' => $this->slug,

      /**
       * Отображаемое название типа цены.
       * @var string
       * @example "Розничная цена"
       */
      'name' => (string) $this->name,

      /**
       * Описание типа цены.
       * @var string|null
       * @example "Основной розничный прайс-лист"
       */
      'description' => $this->description ? (string) $this->description : null,

      /**
       * Флаг базового типа цены по умолчанию.
       * @var bool
       * @example true
       */
      'is_default' => (bool) $this->is_default,

      /**
       * Валюта, привязанная к типу цены.
       */
      'currency' => $this->currency ? new BootstrapBaseCurrencyResource($this->currency) : null,
    ];
  }
}
