<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Support\Constants\MediaCollection;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

trait MapsEavAttributes
{
  protected function getPublicSettings(Model $model): ?object
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $settings = $model->settings['channels'][$channel] ?? [];

    if ($settings[SK::IS_SETTINGS_PUBLIC] ?? false) {
      return (object)$settings;
    }

    return null;
  }

  /**
   * Преобразует коллекцию EAV-значений в ассоциативный массив для API.
   *
   * @return array<string, array{name: string, type: string, is_multiple: bool, value: mixed}>
   */
  protected function mapEavAttributes(Collection $attributeValues): array
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);

    return $attributeValues
      ->filter(function ($val) use ($channel) {
        $settings = is_array($val->attribute->settings) ? $val->attribute->settings : [];
        $chanSettings = $settings['channels'][$channel] ?? [];

        return (bool)($chanSettings[SK::IS_PUBLIC] ?? true);
      })
      ->groupBy(fn($val) => $val->attribute->code)
      ->map(function ($group) {
        $attribute = $group->first()->attribute;

        $mappedValues = $group->map(function ($val) use ($attribute) {

          // Детальный вывод обычных опций (Цвета, Бренды) с их медиа-файлами
          if ($val->option) {
            $meta = is_array($val->option->meta) ? $val->option->meta : [];
            return [
              'slug' => $val->option->slug,
              'name' => (string)$val->option->value,
              'meta' => (object)[
                'hex' => $meta['hex'] ?? null,
                'icon' => $meta['icon'] ?? null,
                'image' => $val->option->getFirstMediaUrl(MediaCollection::MAIN) ?: null,
              ],
            ];
          }

          // Детальный вывод умных справочников (Раскрой, Категории) с метаданными
          if ($val->complexRecord) {
            $payload = $val->complexRecord->meta ?? [];
            $safeMeta = [];

            $schema = $attribute->complexDictionary?->meta_schema ?? [];

            foreach ($schema as $field) {
              $key = $field[SchemaKey::KEY] ?? '';
              $isPublic = $field[SchemaKey::IS_PUBLIC] ?? true;

              if (!$isPublic) {
                continue;
              }

              $safeMeta[$key] = $payload[$key] ?? null;
            }

            return [
              'slug' => $val->complexRecord->external_code ?? (string)$val->complexRecord->id,
              'name' => (string)$val->complexRecord->name,
              'meta' => (object) $safeMeta,
            ];
          }

          // Простые значения (Числа, Строки, Булевы)
          return match (true) {
            $val->value_boolean !== null => (bool)$val->value_boolean,
            $val->value_numeric !== null => (float)$val->value_numeric,
            default => $val->value_string,
          };
        });

        $value = $attribute->is_multiple ? $mappedValues->values()->toArray() : $mappedValues->first();

        return [
          'name' => (string)$attribute->name,
          'type' => $attribute->type,
          'is_multiple' => (bool)$attribute->is_multiple,
          'value' => $value,
        ];
      })
      ->toArray();
  }

}
