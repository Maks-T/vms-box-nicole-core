<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

/**
 * @mixin \Nicole\Box\Core\Models\ProductType
 */
class BootstrapProductTypeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $channel = config('app.channel', Attribute::CHANNEL_WIDGET);
    $isTypeSettingsPublic = $this->settings['channels'][$channel][SK::IS_SETTINGS_PUBLIC] ?? false;

    return [
      /**
       * Символьный код типа товара.
       * @var string
       * @example "acrylic_stone"
       */
      'code' => $this->code,

      /**
       * Отображаемое название типа товара.
       * @var string
       * @example "Акриловый камень"
       */
      'name' => (string) $this->name,

      /**
       * Дополнительные метаданные типа товара (если публичны в канале).
       * @var object
       */
      'meta' => $isTypeSettingsPublic ? (object) ($this->meta ?? []) : (object) [],
    ];
  }
}
