<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Глубокое дерево связей и узлов элементов.
 */
class PipelineTreeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * ID корневой или дочерней модификации товара (SKU).
       * @var int
       * @example 165
       */
      'variant_id' => (int) ($this->resource['variant_id'] ?? 0),

      /**
       * Наименование товара / модификации.
       * @var string
       * @example "Террасная доска ПроДекинг (22 односторонняя) 4000 (Антрацит)"
       */
      'variant_name' => (string) ($this->resource['variant_name'] ?? ''),

      /**
       * Ссылка на фото товара.
       * @var string|null
       * @example "http://localhost/storage/products/165.webp"
       */
      'image_url' => isset($this->resource['image_url']) ? (string) $this->resource['image_url'] : null,

      /**
       * Флаг корректности сборки всех обязательных связей дерева.
       * @var bool
       * @example true
       */
      'is_valid' => (bool) ($this->resource['is_valid'] ?? false),

      /**
       * Массив связей, слотов и вложенных узлов дерева.
       * @var array<int, array{rule_id: int|null, field_code: string, label: string, is_required: bool, is_filled: bool, is_valid: bool, value: mixed, child: array{id: int|string, name: string, slug: string|null, image_url: string|null}|null, static_meta: array|null, children: array<int, mixed>}>
       */
      'fields' => $this->resource['fields'] ?? [],

      /**
       * ЧПУ-слаг родительского товара.
       * @var string|null
       * @example "terrasnaia-doska-prodeking-22-odnostoronniaia-4000-a4795"
       */
      'product_slug' => isset($this->resource['product_slug']) ? (string) $this->resource['product_slug'] : null,

      /**
       * Символьный код вертикали индустрии платформы.
       * @var string|null
       * @example "wpc"
       */
      'pipeline_industry' => isset($this->resource['pipeline_industry']) ? (string) $this->resource['pipeline_industry'] : null,
    ];
  }
}
