<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Order;

/**
 * Главный ресурс детальной информации о заказе (коммерческом предложении).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Внутренний системный ID заказа.
       * @var int
       */
      'id' => $this->id,

      /**
       * Уникальный системный код коммерческого предложения.
       * @var string
       * @example "O-2612-ABCD"
       */
      'code' => $this->code,

      /**
       * Внешний код для интеграции с 1C / ERP (при наличии).
       * @var string|null
       */
      'external_code' => $this->external_code ?? null,

      /**
       * Итоговая сумма заказа с учетом скидок в системной валюте.
       * @var float
       * @example 1990.64
       */
      'grand_total' => (float)$this->grand_total,

      /**
       * Международный трехбуквенный ISO-код валюты заказа.
       * @var string
       * @example "RUB"
       */
      'currency' => $this->currency,

      /**
       * Локаль/язык, на котором был оформлен расчет.
       * @var string|null
       * @example "ru"
       */
      'locale' => $this->locale,

      /**
       * Сохраненное структурированное состояние калькулятора (JSON).
       * @var object
       */
      'calc_state' => $this->calc_state,

      /**
       * Детальные данные привязанного покупателя (подгружаются при наличии связи).
       * @var CustomerResource|null
       */
      'customer' => new CustomerResource($this->whenLoaded('customer')),

      /**
       * Дата и время создания заказа в формате ISO 8601.
       * @var string
       */
      'created_at' => $this->created_at->toIso8601String(),

      /**
       * Дата и время последнего изменения заказа в формате ISO 8601.
       * @var string
       */
      'updated_at' => $this->updated_at->toIso8601String(),
    ];
  }
}