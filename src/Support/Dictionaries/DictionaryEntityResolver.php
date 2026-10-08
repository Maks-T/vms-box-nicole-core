<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Dictionaries;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Support\Constants\EntityType as ET;
use Nicole\Box\Core\Support\Constants\SchemaKey;

/**
 * Универсальный диспетчер привязки системных сущностей к полям умных справочников.
 *
 * @since 2026-10-08
 */
class DictionaryEntityResolver
{
    /**
     * Генерация UI-компонента формы Filament для поля типа entity.
     *
     * @param array<string, mixed> $fieldConfig Конфигурация поля из meta_schema
     * @param string $payloadKey Путь сохранения состояния (например, meta.material_code)
     * @return Field
     */
    public static function resolveFormComponent(array $fieldConfig, string $payloadKey): Field
    {
        $targetEntity = (string) ($fieldConfig[SchemaKey::TARGET_ENTITY] ?? '');
        $valueKey = (string) ($fieldConfig[SchemaKey::VALUE_KEY] ?? 'code');
        $filter = is_array($fieldConfig[SchemaKey::FILTER] ?? null) ? $fieldConfig[SchemaKey::FILTER] : [];
        $label = $fieldConfig[SchemaKey::LABEL] ?? $fieldConfig[SchemaKey::KEY];
        $displayLabel = is_array($label) ? ($label[app()->getLocale()] ?? head($label)) : (string) $label;

        return Select::make($payloadKey)
            ->label((string) $displayLabel)
            ->options(fn () => static::getOptions($targetEntity, $filter, $valueKey))
            ->searchable()
            ->preload();
    }

    /**
     * Получение словаря доступных опций [value => label] с учетом переданных фильтров.
     *
     * @param string $targetEntity Тип целевой сущности из EntityType
     * @param array<string, mixed> $filter Ассоциативный массив ограничений
     * @param string $valueKey Имя поля, сохраняемого в БД (code, slug, id)
     * @param string|null $locale Локаль для перевода названий
     * @return array<string|int, string>
     */
    public static function getOptions(
        string $targetEntity,
        array $filter = [],
        string $valueKey = 'code',
        ?string $locale = null,
    ): array {
        $locale ??= app()->getLocale();

        return match ($targetEntity) {
            ET::PRODUCT_TYPE => static::getProductTypeOptions($filter, $valueKey, $locale),
            ET::CATEGORY => static::getCategoryOptions($filter, $valueKey, $locale),
            default => [],
        };
    }

    /**
     * Разрешение читаемого названия сущности по сохраненному значению ключа.
     *
     * @param array<string, mixed> $fieldConfig Конфигурация поля из meta_schema
     * @param mixed $value Сохраненное значение ключа в JSONB
     * @param string|null $locale Локаль перевода
     * @return string
     */
    public static function resolveDisplayLabel(
        array $fieldConfig,
        mixed $value,
        ?string $locale = null,
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $targetEntity = (string) ($fieldConfig[SchemaKey::TARGET_ENTITY] ?? '');
        $valueKey = (string) ($fieldConfig[SchemaKey::VALUE_KEY] ?? 'code');
        $filter = is_array($fieldConfig[SchemaKey::FILTER] ?? null) ? $fieldConfig[SchemaKey::FILTER] : [];
        $options = static::getOptions($targetEntity, $filter, $valueKey, $locale);

        return $options[(string) $value] ?? (string) $value;
    }

    /**
     * Выборка опций типов товаров с поддержкой фильтрации по семейству.
     */
    protected static function getProductTypeOptions(array $filter, string $valueKey, string $locale): array
    {
        $query = ProductType::query()->where('is_active', true);

        if (!empty($filter['family'])) {
            $families = (array) $filter['family'];
            $query->whereHas('family', fn ($q) => $q->whereIn('code', $families));
        }

        if (!empty($filter['family_id'])) {
            $query->whereIn('family_id', (array) $filter['family_id']);
        }

        return $query->get()->mapWithKeys(function (ProductType $type) use ($valueKey, $locale) {
            $name = $type->getTranslation('name', $locale) ?: $type->name;
            $val = (string) $type->{$valueKey};
            $display = $valueKey !== 'name' ? "{$name} ({$val})" : $name;

            return [$val => $display];
        })->toArray();
    }

    /**
     * Выборка опций категорий с поддержкой фильтрации по родительской категории.
     */
    protected static function getCategoryOptions(array $filter, string $valueKey, string $locale): array
    {
        $query = Category::query()->where('is_active', true);

        if (!empty($filter['parent'])) {
            $parents = (array) $filter['parent'];
            $query->whereHas('parent', fn ($q) => $q->whereIn('slug', $parents));
        }

        return $query->get()->mapWithKeys(function (Category $cat) use ($valueKey, $locale) {
            $name = $cat->getTranslation('name', $locale) ?: $cat->name;
            $val = (string) $cat->{$valueKey};
            $display = $valueKey !== 'name' ? "{$name} ({$val})" : $name;

            return [$val => $display];
        })->toArray();
    }
}