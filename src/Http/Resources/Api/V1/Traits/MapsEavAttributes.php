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
              /**
               * Системный ключ опции (слаг).
               * @var string
               * @example "0,9-mm"
               */
              'key' => $val->option->slug,

              /**
               * Текстовое название опции.
               * @var string
               * @example "0,9 мм"
               */
              'label' => (string)$val->option->value,

              /**
               * Техническое значение параметра для расчетов.
               * @var string|float|bool|null
               * @example 0.9
               */
              'param' => $val->option->param,

              /**
               * Дополнительные медиа-файлы и метаданные опции.
               * @var object
               */
              'meta' => (object)[
                'hex' => $meta['hex'] ?? null,
                'icon' => $meta['icon'] ?? null,
                'image' => $val->option->getFirstMediaUrl(MediaCollection::MAIN) ?: null,
              ],
            ];
          }

          // Детальный вывод умных справочников с метаданными
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
              /**
               * Системный ключ (слаг) записи умного справочника.
               * @var string
               * @example "acr_12"
               */
              'key' => $val->complexRecord->slug ?? ($val->complexRecord->external_code ?? (string)$val->complexRecord->id),

              /**
               * Отображаемое название записи умного справочника.
               * @var string
               * @example "Акрил 12мм"
               */
              'label' => (string)$val->complexRecord->name,

              /**
               * Дополнительные структурированные метаданные записи умного справочника из схемы.
               * @var object
               */
              'meta' => (object)$safeMeta,
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
          /**
           * Название характеристики.
           * @var string
           */
          'name' => (string)$attribute->name,

          /**
           * Тип характеристики (string, numeric, boolean, dictionary, complex).
           * @var string
           */
          'type' => $attribute->type,

          /**
           * Ожидаемый тип данных в поле "param" дочерних опций (none, string, numeric, boolean).
           * @var string|null
           * @example "numeric"
           */
          'param_type' => $attribute->option_param_type,

          /**
           * Является ли характеристика множественной.
           * @var bool
           */
          'is_multiple' => (bool)$attribute->is_multiple,

          /**
           * Текущее значение или список значений характеристики.
           * @var mixed
           */
          'value' => $value,
        ];
      })
      ->toArray();
  }

}
