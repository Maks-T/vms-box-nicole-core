<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Nicole\Box\Core\Models\Currency
 */
class BootstrapBaseCurrencyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $locale = app()->getLocale();

    return [
      /**
       * Международный трехбуквенный ISO-код базовой валюты.
       * @var string
       * @example "RUB"
       */
      'code' => $this->code,

      /**
       * Графический символ базовой валюты.
       * @var string
       * @example "₽"
       */
      'symbol' => $this->symbol,

      /**
       * Официальный нативный символ или сокращение валюты для печатных форм и смет.
       * @var string
       * @example "руб."
       */
      'symbol_native' => $this->getTranslation('symbol_native', $locale) ?? $this->symbol,
    ];
  }
}
