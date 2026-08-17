<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines;

use Nicole\Box\Core\Contracts\PipelineRoleInterface;

/**
 * Универсальный мост для получения отраслевых ролей и их связок по умолчанию
 */
class PipelineRoleResolver
{
  /**
   * Класс констант ролей текущей активной индустрии
   * @var class-string<PipelineRoleInterface>|null
   */
  protected static ?string $roleClass = null;

  /**
   * Регистрация класса констант ролей текущей индустрии
   *
   * @param class-string<PipelineRoleInterface> $class
   */
  public static function register(string $class): void
  {
    self::$roleClass = $class;
  }

  /**
   * Получение мультиязычных опций выбора для Filament-селекта
   *
   * @return array<string, string> [role_code => Label (role_code)]
   */
  public static function getOptions(): array
  {
    if (self::$roleClass && class_exists(self::$roleClass)) {
      return (self::$roleClass)::options();
    }
    return [];
  }

  /**
   * Получение переведенного названия конкретной роли
   */
  public static function getLabel(string $role): string
  {
    if (self::$roleClass && class_exists(self::$roleClass)) {
      return (self::$roleClass)::label($role) ?: $role;
    }
    return $role;
  }

  /**
   * Получение целевого типа и кода по умолчанию для выбранной роли
   *
   * @param string $role
   * @return array{target_type: string, target_code: string|null}|null
   */
  public static function getDefaultTarget(string $role): ?array
  {
    if (self::$roleClass && class_exists(self::$roleClass)) {
      return (self::$roleClass)::defaultTarget($role);
    }
    return null;
  }
}
