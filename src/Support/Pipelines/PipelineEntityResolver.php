<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nicole\Box\Core\Support\Pipelines\Adapters\PipelineEntityAdapterInterface;

/**
 * Универсальный динамический диспетчер адаптеров сущностей пайплайна
 */
class PipelineEntityResolver
{
  /**
   * Зарегистрированные адаптеры, сгруппированные по morph_type и имени класса
   * @var array<string, PipelineEntityAdapterInterface>
   */
  protected static array $adapters = [];

  /**
   * Реестр целевых типов для слотов схемы
   * @var array<string, array{label: string, options_loader: Closure}>
   */
  protected static array $targetEntities = [];

  /**
   * Регистрация адаптера сущности
   */
  public static function registerAdapter(PipelineEntityAdapterInterface $adapter): void
  {
    static::$adapters[$adapter->getModelClass()] = $adapter;
    static::$adapters[$adapter->getMorphType()] = $adapter;
  }

  /**
   * Регистрация целевой сущности для слотов схемы
   */
  public static function registerTargetEntity(string $type, string $label, Closure $optionsLoader): void
  {
    static::$targetEntities[$type] = [
      'label' => $label,
      'options_loader' => $optionsLoader,
    ];
  }

  public static function getAdapter(string|Model $entity): ?PipelineEntityAdapterInterface
  {
    if ($entity instanceof Model) {
      return static::$adapters[get_class($entity)] ?? (static::$adapters[$entity->getMorphClass()] ?? null);
    }

    $morphClass = Relation::getMorphedModel($entity) ?? $entity;
    return static::$adapters[$entity] ?? (static::$adapters[$morphClass] ?? null);
  }

  public static function getAvailableEntityTypes(): array
  {
    $types = [];
    foreach (static::$adapters as $key => $adapter) {
      if ($key === $adapter->getMorphType()) {
        $types[$adapter->getMorphType()] = __($adapter->getLabel());
      }
    }
    return $types;
  }

  public static function getTargetEntityOptions(): array
  {
    return collect(static::$targetEntities)->map(fn($item) => $item['label'])->toArray();
  }

  public static function getTargetCodeOptions(?string $targetType): array
  {
    if (!$targetType || !isset(static::$targetEntities[$targetType])) {
      return [];
    }
    return call_user_func(static::$targetEntities[$targetType]['options_loader']);
  }

  public static function getEntitySelectOptions(string $entityType, ?string $filterTypeCode = null, array $configuredIds = []): array
  {
    $adapter = static::getAdapter($entityType);
    return $adapter ? $adapter->getSelectOptions($filterTypeCode, $configuredIds) : [];
  }

  /**
   * Динамическое создание UI-компонента формы через зарегистрированный адаптер
   */
  public static function resolveSelectComponent(
    string  $entityType,
    string  $fieldName = 'entity_id',
    ?string $filterTypeCode = null,
    bool    $multiple = false
  ): Field
  {
    $adapter = static::getAdapter($entityType);

    if ($adapter) {
      return $adapter->getFormComponent($fieldName, $filterTypeCode, $multiple);
    }

    return Select::make($fieldName)
      ->required()
      ->multiple($multiple)
      ->searchable();
  }

  public static function resolveParentId(Model $entity): ?int
  {
    $adapter = static::getAdapter($entity);
    return $adapter ? $adapter->getParentId($entity) : ($entity->parent_id ?? null);
  }

  public static function resolveName(Model $entity, string $locale): string
  {
    $adapter = static::getAdapter($entity);
    return $adapter ? $adapter->getName($entity, $locale) : ($entity->name ?? 'ID #' . $entity->getKey());
  }

  public static function resolveSlug(Model $entity): ?string
  {
    $adapter = static::getAdapter($entity);
    return $adapter ? $adapter->getSlug($entity) : ($entity->slug ?? null);
  }

  public static function resolveImageUrl(Model $entity): ?string
  {
    $adapter = static::getAdapter($entity);
    return $adapter?->getImageUrl($entity);
  }

  public static function resolveTypeCode(Model $entity): string
  {
    $adapter = static::getAdapter($entity);
    return $adapter ? $adapter->getTypeCode($entity) : (string)($entity->code ?? $entity->getMorphClass());
  }

}
