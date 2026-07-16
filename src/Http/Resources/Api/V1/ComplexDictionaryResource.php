<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;
use Nicole\Box\Core\Support\Constants\SchemaKey;

/**
 * @mixin ComplexDictionary
 */
class ComplexDictionaryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $channel = config('app.channel', 'widget');
    $chanSettings = $this->settings['channels'][$channel] ?? [];

    if (!($chanSettings[SK::IS_PUBLIC] ?? true)) {
      return [];
    }

    $schema = $this->meta_schema ?? [];
    $locale = app()->getLocale();

    $isSettingsPublic = $chanSettings[SK::IS_SETTINGS_PUBLIC] ?? false;
    $publicSchema = null;

    if ($isSettingsPublic && is_array($schema)) {
      $publicSchema = [];
      foreach ($schema as $field) {
        if (!($field[SchemaKey::IS_PUBLIC] ?? true)) continue;

        $label = is_array($field[SchemaKey::LABEL])
          ? ($field[SchemaKey::LABEL][$locale] ?? $field[SchemaKey::KEY])
          : ($field[SchemaKey::LABEL] ?? $field[SchemaKey::KEY]);

        $publicSchema[] = [
          SchemaKey::KEY => $field[SchemaKey::KEY],
          SchemaKey::TYPE => $field[SchemaKey::TYPE],
          SchemaKey::LABEL => $label,
        ];
      }
    }

    return [
      /**
       * Системный код умного справочника (напр., price_group).
       * @var string
       */
      'code' => $this->code,

      /**
       * Название справочника.
       * @var string
       */
      'name' => (string)$this->name,

      /**
       * Схема полей справочника.
       * @var array<int, array{key: string, type: string, label: string}>|null
       */
      'schema' => $publicSchema,

      /**
       * Элементы справочника.
       * @var array<int, array{id: int, key: string, label: string, meta: object}>
       */
      'records' => $this->records
        ->map(function ($record) use ($schema) {
          $payload = $record->meta ?? [];
          $safeMeta = [];

          foreach ($schema as $field) {
            $key = $field[SchemaKey::KEY];
            $isFieldPublic = $field[SchemaKey::IS_PUBLIC] ?? true;

            if (!$isFieldPublic) continue;

            $safeMeta[$key] = $payload[$key] ?? null;
          }

          return [
            /**
             * Системный ID записи умного справочника.
             * @var int
             */
            'id' => $record->id,

            /**
             * Системный ключ (слаг) записи справочника.
             * @var string
             * @example "acr_12"
             */
            'key' => $record->slug ?? ($record->external_code ?? (string)$record->id),

            /**
             * Отображаемое название записи.
             * @var string
             * @example "Акрил 12мм"
             */
            'label' => (string)$record->name,

            /**
             * Дополнительные структурированные метаданные записи согласно схеме.
             * @var object
             */
            'meta' => (object)$safeMeta,
          ];
        })
        ->toArray(),
    ];
  }
}
