<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

/**
 * @mixin \Nicole\Box\Core\Models\ProductFamily
 */
class BootstrapFamilyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $locale = app()->getLocale();

    $isFamilySettingsPublic = $this->settings['channels'][$channel][SK::IS_SETTINGS_PUBLIC] ?? false;
    $schema = null;

    if ($isFamilySettingsPublic && is_array($this->meta_schema)) {
      $schema = [];
      foreach ($this->meta_schema as $field) {
        $label = is_array($field[SchemaKey::LABEL] ?? null)
          ? ($field[SchemaKey::LABEL][$locale] ?? ($field[SchemaKey::KEY] ?? ''))
          : ($field[SchemaKey::LABEL] ?? ($field[SchemaKey::KEY] ?? ''));

        $schema[] = [
          'key' => $field[SchemaKey::KEY] ?? '',
          'type' => $field[SchemaKey::TYPE] ?? 'text',
          'label' => (string) $label,
        ];
      }
    }

    return [
      /**
       * Символьный код семейства товаров.
       * @var string
       * @example "stone"
       */
      'code' => $this->code,

      /**
       * Отображаемое название семейства товаров.
       * @var string
       * @example "Камень"
       */
      'name' => (string) $this->name,

      /**
       * Публичная схема физических параметров семейства.
       * @var array<int, array{key: string, type: string, label: string}>|null
       */
      'schema' => $schema,

      /**
       * Список связанных активных типов товаров.
       * @var \Illuminate\Http\Resources\Json\AnonymousResourceCollection<BootstrapProductTypeResource>
       */
      'types' => BootstrapProductTypeResource::collection($this->whenLoaded('types')),
    ];
  }
}
