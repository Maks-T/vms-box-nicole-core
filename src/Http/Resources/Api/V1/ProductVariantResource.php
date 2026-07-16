<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Http\Resources\Api\V1\Traits\MapsEavAttributes;

/**
 * Ресурс модификации (SKU) товара.
 *
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
  use MapsEavAttributes;

  public function toArray(Request $request): array
  {
    $pricingManager = app(PricingManager::class);

    return [
      /**
       * Внутренний ID модификации (SKU).
       * @var int
       * @example 45
       */
      'id' => $this->id,

      /**
       * Артикул (SKU) модификации, выступающий её уникальным техническим кодом.
       * @var string
       * @example "p-101-pure-vanilla-3680"
       */
      'sku' => $this->sku,

      /**
       * Внешний код для интеграции с 1C / ERP.
       * @var string|null
       */
      'external_code' => $this->external_code ?? null,

      /**
       * Название модификации (если пусто, наследуется имя родителя).
       * @var string
       */
      'name' => (string)($this->name ?? $this->product?->name),

      /**
       * Доступный остаток на складах.
       * @var float
       * @example 10.0
       */
      'stock' => (float) $this->stock,

      /**
       * Является ли этот вариант дефолтным для товара.
       * @var bool
       */
      'is_default' => (bool) $this->is_default,

      /**
       * Карта цен для доступных в канале прайс-листов (slug => цена).
       * @var array<string, float>
       * @example {"retail": 18500.0}
       */
      'prices' => $pricingManager->getVariantPricesMap($this->resource),

      /**
       * URL картинки превью вариации.
       * @var string|null
       */
      'preview_picture' => $this->getPreviewUrl(),

      /**
       * URL детальной картинки вариации.
       * @var string|null
       */
      'detail_picture' => $this->getDetailUrl(),

      /**
       * Динамические характеристики (EAV) модификации.
       * @var array<string, array{name: string, type: string, param_type: string|null, is_multiple: bool, value: mixed}>
       */
      'attributes' => $this->mapEavAttributes($this->attributeValues ?? collect()),

      /**
       * Настройки канала модификации.
       * @var object|null
       */
      'settings' => $this->getPublicSettings($this->resource),
    ];
  }

}
