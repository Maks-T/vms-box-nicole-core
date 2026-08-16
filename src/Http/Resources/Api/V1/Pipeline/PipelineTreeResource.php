<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Глубокое дерево связей, слотов и узлов элементов конфигуратора.
 */
class PipelineTreeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Полиморфный тип корневой сущности узла (например: product_variant, complex_dictionary_record, product).
       * @var string
       * @example "product_variant"
       */
      'type' => (string) ($this->resource['type'] ?? 'product_variant'),

      /**
       * Уникальный ID сущности текущего узла.
       * @var int
       * @example 165
       */
      'id' => (int) ($this->resource['id'] ?? 0),

      /**
       * ID родительской базовой сущности (для SKU — ID базового товара, для записи — ID справочника).
       * @var int|null
       * @example 81
       */
      'parent_id' => isset($this->resource['parent_id']) ? (int) $this->resource['parent_id'] : null,

      /**
       * Название сущности узла.
       * @var string
       * @example "Террасная доска ПроДекинг (22 односторонняя) 4000 (Антрацит)"
       */
      'name' => (string) ($this->resource['name'] ?? ''),

      /**
       * Уникальный ЧПУ-идентификатор (слаг) сущности или родительского товара.
       * @var string|null
       * @example "terrasnaia-doska-prodeking-22-odnostoronniaia-4000-a4795"
       */
      'slug' => isset($this->resource['slug']) ? (string) $this->resource['slug'] : null,

      /**
       * URL изображения или превью текущего узла.
       * @var string|null
       * @example "https://wpc.vistegra-admin.ru/storage/catalog/product_variant/165/preview/thumbnail.webp"
       */
      'image_url' => isset($this->resource['image_url']) ? (string) $this->resource['image_url'] : null,

      /**
       * Флаг корректности сборки всех обязательных связей и зависимостей дерева.
       * @var bool
       * @example true
       */
      'is_valid' => (bool) ($this->resource['is_valid'] ?? false),

      /**
       * Список слотов, связей и вложенных дочерних узлов дерева.
       * @var array<int, array{
       *   rule_id: int|null,
       *   field_code: string,
       *   label: string,
       *   is_required: bool,
       *   is_filled: bool,
       *   is_valid: bool,
       *   value: string|float|int|bool|null,
       *   child: array{
       *     type: string,
       *     id: int,
       *     parent_id: int|null,
       *     name: string,
       *     slug: string|null,
       *     image_url: string|null
       *   }|null,
       *   static_meta: array<string, mixed>|null,
       *   children: array<int, mixed>,
       *   virtual_meta?: array{
       *     parent_id: int,
       *     parent_type: string,
       *     role: string,
       *     pipeline_id: int,
       *     target_type: string,
       *     target_code: string|null
       *   }
       * }>
       */
      'fields' => $this->resource['fields'] ?? [],

      /**
       * Символьный код вертикали индустрии платформы.
       * @var string|null
       * @example "wpc"
       */
      'pipeline_industry' => isset($this->resource['pipeline_industry']) ? (string) $this->resource['pipeline_industry'] : null,
    ];
  }
}
