<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Calculator;

class PipelineRoleResolver
{
  /**
   * Карта соответствия индустрий и их классов констант ролей
   */
  protected static array $mappings = [];

  /**
   * Метод для регистрации отраслевого класса констант
   */
  public static function register(string $industry, string $class): void
  {
    self::$mappings[$industry] = $class;
  }

  /**
   * Получение мультиязычных опций выбора для Filament-селекта
   */
  public static function getOptions(string $industry): array
  {
    $class = self::$mappings[$industry] ?? null;
    if ($class && class_exists($class) && method_exists($class, 'options')) {
      return $class::options();
    }
    return [];
  }

  /**
   * Получение переведенного названия конкретной роли
   */
  public static function getLabel(string $industry, string $role): string
  {
    $class = self::$mappings[$industry] ?? null;
    if ($class && class_exists($class) && method_exists($class, 'label')) {
      return $class::label($role) ?: $role;
    }
    return $role;
  }

  /**
   * Получение дефолтного типа товара для автозаполнения формы
   */
  public static function getDefaultProductType(string $industry, string $role): ?string
  {
    $class = self::$mappings[$industry] ?? null;
    if ($class && class_exists($class) && method_exists($class, 'defaultProductType')) {
      return $class::defaultProductType($role);
    }
    return null;
  }
}
