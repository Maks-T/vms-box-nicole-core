<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Http\Resources\Api\V1\ComplexDictionaryResource;

class BootstrapDataResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Список поддерживаемых языков/локалей в системе.
       * @var array<int, string>
       * @example ["ru", "en"]
       */
      'languages' => config('nicole.locales', ['ru', 'en']),

      /**
       * Базовая валюта системы.
       */
      'base_currency' => new BootstrapBaseCurrencyResource($this->resource['base_currency']),

      /**
       * Доступные типы цен в этом канале продаж.
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<BootstrapPriceTypeResource>
       */
      'price_types' => BootstrapPriceTypeResource::collection($this->resource['price_types']),

      /**
       * Умные справочники (матрицы цен, коэффициенты толщин, группы раскроя).
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<ComplexDictionaryResource>
       */
      'dictionaries' => $this->resource['dictionaries'],

      /**
       * Дерево каталога: Семейства и вложенные Типы товаров.
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<BootstrapFamilyResource>
       */
      'families' => BootstrapFamilyResource::collection($this->resource['families']),

      /**
       * Активные пайплайны конфигураторов со схемами слотов и зависимостей.
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<BootstrapPipelineResource>
       */
      'pipelines' => BootstrapPipelineResource::collection($this->resource['pipelines']),
    ];
  }
}
