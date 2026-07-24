<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines;

class PipelineRoleResolver
{
    /**
     * Класс констант ролей текущей активной индустрии
     */
    protected static ?string $roleClass = null;

    /**
     * Регистрация класса констант ролей текущей индустрии
     */
    public static function register(string $class): void
    {
        self::$roleClass = $class;
    }

    /**
     * Получение мультиязычных опций выбора для Filament-селекта
     */
    public static function getOptions(): array
    {
        if (self::$roleClass && class_exists(self::$roleClass) && method_exists(self::$roleClass, 'options')) {
            return (self::$roleClass)::options();
        }
        return [];
    }

    /**
     * Получение переведенного названия конкретной роли (1 аргумент!)
     */
    public static function getLabel(string $role): string
    {
        if (self::$roleClass && class_exists(self::$roleClass) && method_exists(self::$roleClass, 'label')) {
            return (self::$roleClass)::label($role) ?: $role;
        }
        return $role;
    }

    /**
     * Получение дефолтного типа товара для автозаполнения слота формы
     */
    public static function getDefaultProductType(string $role): ?string
    {
        if (self::$roleClass && class_exists(self::$roleClass) && method_exists(self::$roleClass, 'defaultProductType')) {
            return (self::$roleClass)::defaultProductType($role);
        }
        return null;
    }
}
