<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс корневой модификации (SKU) для старта расчета в пайплайне.
 *
 * @mixin \Nicole\Box\Core\Models\ProductVariant
 */
class PipelineRootVariantResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $locale = app()->getLocale();

    return [
      /**
       * ID корневой модификации товара (baseVariantId).
       * @var int
       * @example 45
       */
      'id' => $this->id,

      /**
       * Артикул (SKU) модификации.
       * @var string
       * @example "terrace-board-velvet-3000"
       */
      'sku' => $this->sku,

      /**
       * Название товара или модификации.
       * @var string
       * @example "Террасная доска Velvet"
       */
      'name' => (string) ($this->getTranslation('name', $locale) ?: ($this->product?->getTranslation('name', $locale) ?: $this->name)),

      /**
       * ЧПУ-слаг родительского товара.
       * @var string|null
       * @example "terrace-board-velvet"
       */
      'product_slug' => $this->product?->slug,

      /**
       * Превью-изображение товара.
       * @var string|null
       */
      'preview_picture' => $this->getPreviewUrl() ?: $this->product?->getPreviewUrl(),
    ];
  }
}
