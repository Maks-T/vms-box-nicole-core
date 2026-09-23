<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Order;

/**
 * Ресурс элемента списка сохраненных заказов (для модального окна "Открыть проект").
 *
 * @mixin Order
 * @since 2026-09-06
 */
class OrderListItemResource extends JsonResource
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
       * Уникальный номер (код) заказа.
       * @var string
       * @example "O-261-ODR6"
       */
      'code' => (string) $this->code,

      /**
       * Название проекта / расчета.
       * @var string|null
       * @example "Терраса у дома"
       */
      'name' => (string) ($this->name ?? $this->title ?? ''),

      /**
       * Алиас названия для обратной совместимости со старыми виджетами.
       * @var string|null
       * @example "Терраса у дома"
       */
      'title' => (string) ($this->name ?? $this->title ?? ''),

      /**
       * Итоговая сумма заказа в валюте расчета.
       * @var float
       * @example 145184.00
       */
      'grand_total' => (float) $this->grand_total,

      /**
       * Трехбуквенный ISO-код валюты заказа.
       * @var string
       * @example "RUB"
       */
      'currency' => (string) $this->currency,

      /**
       * Статус заказа.
       * @var array{slug: string, name: string, color: string}|null
       */
      'status' => $this->status ? [
        'slug' => (string) $this->status->slug,
        'name' => (string) ($this->status->getTranslation('name', app()->getLocale()) ?? $this->status->name),
        'color' => (string) ($this->status->color ?? 'gray'),
      ] : null,

      /**
       * Краткие данные покупателя.
       * @var array{name: string, phone: string}|null
       */
      'customer' => $this->customer ? [
        'name' => (string) ($this->customer->full_name ?? ($this->customer->first_name ?? 'Покупатель')),
        'phone' => (string) ($this->customer->phone ?? ''),
      ] : null,

      /**
       * Имя ответственного менеджера.
       * @var string|null
       * @example "Анна Менеджер"
       */
      'manager_name' => $this->manager ? (string) $this->manager->name : null,

      /**
       * Дата и время создания в формате ISO 8601.
       * @var string
       * @example "2026-09-06T06:23:01+00:00"
       */
      'created_at' => $this->created_at->toIso8601String(),

      /**
       * Человекочитаемая форматированная дата создания.
       * @var string
       * @example "06.09.2026 06:23"
       */
      'created_at_formatted' => $this->created_at->format('d.m.Y H:i'),

      /**
       * Ссылка на скачивание PDF-версии коммерческого предложения.
       * @var string
       * @example "https://vms-nc/api/v1/orders/O-261-ODR6/pdf"
       */
      'pdf_url' => url("/api/v1/orders/{$this->code}/pdf"),

      /**
       * Ссылка на просмотр чистой HTML-версии сметы.
       * @var string
       * @example "https://vms-nc/api/v1/orders/O-261-ODR6/html"
       */
      'html_url' => url("/api/v1/orders/{$this->code}/html"),
    ];
  }
}