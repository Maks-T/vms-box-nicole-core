<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;

/**
 * Единый контракт адаптера сущности для системы пайплайнов
 */
interface PipelineEntityAdapterInterface
{
  /**
   * Человекочитаемое название типа сущности
   */
  public function getLabel(): string;

  /**
   * Класс Eloquent-модели, обслуживаемый адаптером
   * @return class-string<Model>
   */
  public function getModelClass(): string;

  /**
   * Морф-тип сущности (product_variant, product и т.д.)
   */
  public function getMorphType(): string;

  /**
   * ID родительской сущности для API (parent_id)
   */
  public function getParentId(Model $entity): ?int;

  /**
   * Наименование сущности под текущую локаль
   */
  public function getName(Model $entity, string $locale): string;

  /**
   * ЧПУ-слаг сущности или родительского товара
   */
  public function getSlug(Model $entity): ?string;

  /**
   * URL превью-изображения сущности
   */
  public function getImageUrl(Model $entity): ?string;

  /**
   * Системный код типа для сопоставления со слотами схемы
   */
  public function getTypeCode(Model $entity): string;

  /**
   * Список опций со стилизованным рендерингом для селектов Filament
   *
   * @param string|null $filterTypeCode
   * @param array<int> $configuredIds
   * @return array<int|string, string>
   */
  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array;

  /**
   * Создание специализированного UI-компонента формы Filament
   */
  public function getFormComponent(string $fieldName = 'entity_id', ?string $filterTypeCode = null, bool $multiple = false): Field;
}
