<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Support\Constants\MediaCollection;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

/**
 * Ресурс атрибута для построения UI фильтров каталога.
 *
 * @mixin Attribute
 */
class FilterResource extends JsonResource
{

  public function toArray(Request $request): array
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);

    $options = $this->options
      ->map(function ($opt) {
        $meta = is_array($opt->meta) ? $opt->meta : [];

        if ($imageUrl = $opt->getFirstMediaUrl(MediaCollection::MAIN)) {
          $meta['image'] = $imageUrl;
        }

        return [
          /**
           * Системный ключ опции фильтра (слаг).
           * @var string
           * @example "0,9-mm"
           */
          'key' => $opt->slug,

          /**
           * Текстовое название опции для людей.
           * @var string
           * @example "0,9 мм"
           */
          'label' => (string) $opt->value,

          /**
           * Техническое значение параметра для калькуляторов и логики.
           * @var string|float|bool|null
           * @example 0.9
           */
          'param' => $opt->param,

          /**
           * Дополнительные метаданные опции.
           * @var object
           */
          'meta' => (object) $meta,
        ];
      })
      ->toArray();

    $allSettings = is_array($this->settings) ? $this->settings : [];
    $chanSettings = $allSettings['channels'][$channel] ?? [];

    $publicSettings = Arr::except($chanSettings, [
      SK::IS_PUBLIC,
      SK::IS_SETTINGS_PUBLIC,
      SK::IS_FILTERABLE,
      SK::IS_ENABLED,
    ]);

    return [
      /**
       * Системный код фильтра (например, color, brand).
       * @var string
       * @example "color"
       */
      'code' => $this->code,

      /**
       * Название фильтра для отображения в интерфейсе.
       * @var string
       * @example "Цвет"
       */
      'name' => (string) $this->name,

      /**
       * Тип данных атрибута (dictionary, boolean, numeric).
       * @var string
       * @example "dictionary"
       */
      'type' => $this->type,

      /**
       * Ожидаемый тип данных в поле "param" (none, string, numeric, boolean).
       * Помогает определить формат технического значения для расчетов.
       * @var string|null
       * @example "numeric"
       */
      'param_type' => $this->option_param_type,

      /**
       * Настройки отображения из мета-схемы (filter_type, is_collapsed и т.д.).
       * @var object
       * @example {"filter_type": "color", "is_collapsed": false}
       */
      'settings' => (object) $publicSettings,

      /**
       * Доступные опции для фильтрации (возвращается, если type = dictionary).
       * @var array<int, array{key: string, label: string, param: string|float|bool|null, meta: object}>
       */
      'options' => $options,
    ];
  }

}
