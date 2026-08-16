<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Illuminate\Database\Eloquent\Model;

interface PipelineEntityAdapterInterface
{
  /**
   * Человекочитаемое название типа сущности.
   */
  public function getLabel(): string;

  /**
   * Класс Eloquent-модели.
   * @return class-string<Model>
   */
  public function getModelClass(): string;

  /**
   * Морф-тип сущности (product_variant, product и т.д.).
   */
  public function getMorphType(): string;

  /**
   * ID родительской сущности для API (parent_id).
   */
  public function getParentId(Model $entity): ?int;

  /**
   * Наименование сущности под текущую локаль.
   */
  public function getName(Model $entity, string $locale): string;

  /**
   * ЧПУ-слаг сущности.
   */
  public function getSlug(Model $entity): ?string;

  /**
   * URL превью-изображения.
   */
  public function getImageUrl(Model $entity): ?string;

  /**
   * Системный код типа для сопоставления со слотами схемы.
   */
  public function getTypeCode(Model $entity): string;

  /**
   * Список опций со стилизованным рендерингом для селектов Filament.
   *
   * @param string|null $filterTypeCode Фильтрация по типу
   * @param array<int> $configuredIds Список уже задействованных ID
   * @return array<int|string, string>
   */
  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array;
}
