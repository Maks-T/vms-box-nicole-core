<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Pipeline;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

/**
 * Ресурс корневой сущности для старта расчета в пайплайне (EntityReference).
 *
 * @mixin Model
 */
class PipelineRootEntityResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $locale = app()->getLocale();
    $entity = $this->resource;

    return [
      /**
       * Полиморфный тип сущности (например: product_variant, complex_dictionary_record, product).
       * @var string
       * @example "product_variant"
       */
      'type' => $entity->getMorphClass(),

      /**
       * Уникальный ID сущности (SKU / записи).
       * @var int
       * @example 165
       */
      'id' => (int)$entity->getKey(),

      /**
       * ID базового родителя (для SKU — ID базового товара, для записи — ID справочника).
       * @var int|null
       * @example 89
       */
      'parent_id' => PipelineEntityResolver::resolveParentId($entity),

      /**
       * Артикул сущности (при наличии).
       * @var string|null
       * @example "00586"
       */
      'sku' => $entity->sku ?? null,

      /**
       * Название сущности.
       * @var string
       * @example "Террасная доска ПроДекинг (22 односторонняя) 4000 (Антрацит)"
       */
      'name' => PipelineEntityResolver::resolveName($entity, $locale),

      /**
       * ЧПУ-слаг сущности или родительского товара.
       * @var string|null
       * @example "terrasnaia-doska-prodeking-22-odnostoronniaia-4000-a4795"
       */
      'product_slug' => PipelineEntityResolver::resolveSlug($entity),

      /**
       * URL превью-изображения.
       * @var string|null
       * @example "https://wpc.vistegra-admin.ru/storage/catalog/product_variant/165/preview/thumbnail.webp"
       */
      'preview_picture' => PipelineEntityResolver::resolveImageUrl($entity),
    ];
  }
}
