<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Http\Resources\Api\V1\Traits\HasSharedResourceFields;

/**
 * Главный ресурс базового товара каталога.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
  use HasSharedResourceFields;

  public function toArray(Request $request): array
  {
    $pricingManager = app(PricingManager::class);

    // Связываем родительскую модель товара с его вариациями в оперативной памяти PHP
    if ($this->relationLoaded('variants')) {
      $this->variants->each(function ($variant) {
        $variant->setRelation('product', $this->resource);
      });
    }

    return array_merge($this->getSharedEntityFields($this->resource), [
      /**
       * Код типа товара.
       * @var string|null
       * @example "acrylic_stone"
       */
      'product_type' => $this->type?->code,

      /**
       * Информация о единице измерения
       * @var array{slug: string, name: string, symbol: string}|null
       */
      'unit' => $this->unit ? [
        'slug' => $this->unit->slug,
        'name' => (string)$this->unit->name,
        'symbol' => (string)$this->unit->symbol,
      ] : null,

      /**
       * Базовая розничная цена "От" в системной валюте.
       * @var float
       * @example 15357.50
       */
      'price_from' => (float) $pricingManager->getRetailPrice($this->resource),

      /**
       * Список доступных модификаций (SKU) товара.
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Nicole\Box\Core\Http\Resources\Api\V1\ProductVariantResource>
       */
      'variants' => ProductVariantResource::collection($this->variants->where('is_active', true)->values())->resolve(),
    ]);
  }
}
