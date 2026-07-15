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

        $label = is_array($field[SchemaKey::LABEL]) ? ($field[SchemaKey::LABEL][$locale] ?? $field[SchemaKey::KEY]) : ($field[SchemaKey::LABEL] ?? $field[SchemaKey::KEY]);

        $publicSchema[] = [
          SchemaKey::KEY => $field[SchemaKey::KEY],
          SchemaKey::TYPE => $field[SchemaKey::TYPE],
          SchemaKey::LABEL => $label,
        ];
      }
    }

    return [
      /**
       * Системный код справочника (напр., price_group).
       * @var string
       */
      'code' => $this->code,

      /**
       * Название справочника.
       * @var string
       */
      'name' => (string)$this->name,

      /**
       * Схема полей.
       * @var array<int, array{key: string, type: string, label: string}>|null
       */
      'schema' => $publicSchema,

      /**
       * Элементы справочника.
       * @var array<int, array{id: int, slug: string, name: string, meta: object}>
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
            'id' => $record->id,
            'slug' => $record->slug ?? ($record->external_code ?? (string)$record->id),
            'name' => (string)$record->name,
            'meta' => (object)$safeMeta,
          ];
        })
        ->toArray(),
    ];
  }
}
