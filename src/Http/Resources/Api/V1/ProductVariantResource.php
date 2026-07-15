<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Http\Resources\Api\V1\Traits\HasSharedResourceFields;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
  use HasSharedResourceFields;

  public function toArray(Request $request): array
  {
    $pricingManager = app(PricingManager::class);

    return array_merge($this->getSharedEntityFields($this->resource), [
      /**
       * Артикул (SKU) модификации
       * @var string
       */
      'sku' => $this->sku,

      /**
       * Карта цен для доступных в канале прайс-листов (slug => цена).
       * @var array<string, float>
       * @example {"retail": 18500.0, "wholesale": 14500.0}
       */
      'prices' => $pricingManager->getVariantPricesMap($this->resource),

      /**
       * Доступный остаток на складах.
       * @var float
       */
      'stock' => (float) $this->stock,

      /**
       * Является ли этот вариант дефолтным.
       * @var bool
       */
      'is_default' => (bool) $this->is_default,
    ]);
  }
}
