<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Http\Resources\Api\V1\Traits\MapsEavAttributes;

/**
 * Ресурс базового товара каталога.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
  use MapsEavAttributes;

  public function toArray(Request $request): array
  {
    $pricingManager = app(PricingManager::class);

    // Связываем родительскую модель товара с его вариациями в оперативной памяти PHP
    if ($this->relationLoaded('variants')) {
      $this->variants->each(function ($variant) {
        $variant->setRelation('product', $this->resource);
      });
    }

    return [
      /**
       * Внутренний системный ID товара/услуги.
       * @var int
       * @example 12
       */
      'id' => $this->id,

      /**
       * Системный код товара для технических расчетов.
       * @var string
       * @example "omoikiri_faucet_01"
       */
      'code' => $this->code,

      /**
       * Уникальный идентификатор для URL (ЧПУ).
       * @var string
       * @example "grandex-m-701"
       */
      'slug' => $this->slug,

      /**
       * Внешний код для интеграции с 1C / ERP.
       * @var string|null
       */
      'external_code' => $this->external_code ?? null,

      /**
       * Название товара/услуги.
       * @var string
       * @example "Pure Vanilla"
       */
      'name' => (string)$this->name,

      /**
       * Тип сущности в каталоге (product, service, bundle).
       * @var string
       * @example "product"
       */
      'catalog_type' => $this->catalog_type,

      /**
       * Код типа товара.
       * @var string|null
       * @example "acrylic_stone"
       */
      'product_type' => $this->type?->code,

      /**
       * Краткое описание товара (анонс).
       * @var string|null
       */
      'short_description' => $this->short_description ? (string)$this->short_description : null,

      /**
       * Полное описание товара.
       * @var string|null
       */
      'description' => $this->description ? (string)$this->description : null,

      /**
       * Информация о единице измерения.
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
       * URL картинки превью.
       * @var string|null
       * @example "/storage/catalog/product/12/preview/thumbnail.webp"
       */
      'preview_picture' => $this->getPreviewUrl(),

      /**
       * URL детальной картинки.
       * @var string|null
       * @example "/storage/catalog/product/12/main/detail.png"
       */
      'detail_picture' => $this->getDetailUrl(),

      /**
       * Динамические характеристики (EAV) товара.
       * @var array<string, array{name: string, type: string, param_type: string|null, is_multiple: bool, value: mixed}>
       */
      'attributes' => $this->mapEavAttributes($this->attributeValues ?? collect()),

      /**
       * Настройки канала товара.
       * @var object|null
       */
      'settings' => $this->getPublicSettings($this->resource),

      /**
       * Список доступных модификаций (SKU) товара.
       * @var array<int, array>
       */
      'variants' => ProductVariantResource::collection($this->variants->where('is_active', true)->values())->resolve(),
    ];
  }

}
