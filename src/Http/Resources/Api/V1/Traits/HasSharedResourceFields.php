<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasSharedResourceFields
{
  use MapsEavAttributes;

  /**
   * Собирает базовые поля, общие для Товаров, Услуг и SKU.
   */
  protected function getSharedEntityFields(Model $model): array
  {
    return [
      /**
       * Внутренний ID сущности (товара, услуги или модификации).
       * @var int
       * @example 12
       */
      'id' => $model->id,

      /**
       * Системный код сущности для технических расчетов и интеграций.
       * Для товаров/услуг берется из поля 'code', для SKU — из 'sku'.
       * @var string|null
       * @example "cutout_top"
       */
      'code' => $model->code ?? ($model->sku ?? null),

      /**
       * Внешний код для интеграции с 1C / ERP.
       * @var string|null
       */
      'external_code' => $model->external_code ?? null,

      /**
       * Уникальный идентификатор для URL (ЧПУ).
       * @var string|null
       * @example "grandex-m-701"
       */
      'slug' => $model->slug ?? null,

      /**
       * Название (для SKU может возвращаться его код, если отдельного имени нет).
       * @var string
       */
      'name' => (string)($model->name ?? $model->sku),

      /**
       * URL картинки превью.
       * @var string|null
       * @example "/storage/catalog/product/12/preview/thumbnail.webp"
       */
      'preview_picture' => method_exists($model, 'getPreviewUrl') ? $model->getPreviewUrl() : null,

      /**
       * URL детальной картинки.
       * @var string|null
       * @example "/storage/catalog/product/12/main/detail.png"
       */
      'detail_picture' => method_exists($model, 'getDetailUrl') ? $model->getDetailUrl() : null,

      /**
       * Динамические характеристики (EAV).
       * Ключом выступает системный код атрибута.
       *
       * @var array<string, array{name: string, type: string, is_multiple: bool, value: mixed}>
       */
      'attributes' => $this->mapEavAttributes($model->attributeValues ?? collect()),

      /**
       * Настройки канала сущности (передаются, если включена опция is_settings_public).
       * @var object|null
       */
      'settings' => $this->getPublicSettings($model),
    ];
  }

}
