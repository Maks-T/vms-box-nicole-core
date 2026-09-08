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
 * @since 2026-09-06
 */
class OrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      /**
       * Внутренний системный ID заказа.
       * @var int
       * @example 1
       */
      'id' => (int) $this->id,

      /**
       * Уникальный системный код коммерческого предложения.
       * @var string
       * @example "O-261-ODR6"
       */
      'code' => (string) $this->code,

      /**
       * Внешний код для интеграции с 1C / ERP (при наличии).
       * @var string|null
       * @example "afad79dc-3276-4681-ab41-8897876ec9fe"
       */
      'external_code' => $this->external_code ? (string) $this->external_code : null,

      /**
       * Название проекта / расчета.
       * @var string|null
       * @example "Терраса у дома"
       */
      'name' => (string) ($this->name ?? $this->title ?? ''),

      /**
       * Итоговая сумма заказа с учетом скидок в системной валюте.
       * @var float
       * @example 145184.00
       */
      'grand_total' => (float) $this->grand_total,

      /**
       * Международный трехбуквенный ISO-код валюты заказа.
       * @var string
       * @example "RUB"
       */
      'currency' => (string) $this->currency,

      /**
       * Локаль/язык, на котором был оформлен расчет.
       * @var string|null
       * @example "ru"
       */
      'locale' => $this->locale ? (string) $this->locale : 'ru',

      /**
       * Сохраненное структурированное состояние калькулятора (JSON).
       * @var object|array
       */
      'calc_state' => $this->calc_state ?? [],

      /**
       * Детальные данные привязанного покупателя (подгружаются при наличии связи).
       * @var CustomerResource|null
       */
      'customer' => new CustomerResource($this->whenLoaded('customer')),

      /**
       * Комментарий клиента к расчету.
       * @var string|null
       * @example "Доставка в первой половине дня"
       */
      'customer_comment' => $this->customer_comment ? (string) $this->customer_comment : null,

      /**
       * Внутренний комментарий менеджера к расчету.
       * @var string|null
       * @example "Согласована скидка 5% на монтаж"
       */
      'manager_comment' => $this->manager_comment ? (string) $this->manager_comment : null,

      /**
       * Прямая ссылка на скачивание PDF-версии коммерческого предложения.
       * @var string
       * @example "https://vms-nc//api/v1/orders/O-261-ODR6/pdf"
       */
      'pdf_url' => url("/api/v1/orders/{$this->code}/pdf"),

      /**
       * Прямая ссылка на просмотр чистой HTML-версии сметы.
       * @var string
       * @example "https://vms-nc//api/v1/orders/O-261-ODR6/html"
       */
      'html_url' => url("/api/v1/orders/{$this->code}/html"),

      /**
       * Дата и время создания заказа в формате ISO 8601.
       * @var string
       * @example "2026-09-06T06:23:01+00:00"
       */
      'created_at' => $this->created_at->toIso8601String(),

      /**
       * Дата и время последнего изменения заказа в формате ISO 8601.
       * @var string
       * @example "2026-09-06T06:23:01+00:00"
       */
      'updated_at' => $this->updated_at->toIso8601String(),
    ];
  }

}