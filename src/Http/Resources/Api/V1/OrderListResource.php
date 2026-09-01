<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Nicole\Box\Core\Models\Order;

/**
 * Легковесный ресурс заказа для списков, таблиц и истории расчетов в калькуляторе.
 *
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    // Берем название первого изделия в заказе, либо дефолтное название
    $firstSectionTitle = $this->sections->first()?->title;
    $orderTitle = $firstSectionTitle ?: ('Расчет ' . $this->code);

    return [
      'id' => $this->id,
      'code' => $this->code,
      'title' => $orderTitle,
      'grand_total' => (float)$this->grand_total,
      'currency' => $this->currency,
      'status' => $this->status ? [
        'slug' => $this->status->slug,
        'name' => (string)$this->status->name,
        'color' => $this->status->color,
      ] : null,
      'customer' => $this->customer ? [
        'name' => $this->customer->full_name,
        'phone' => $this->customer->phone,
      ] : null,
      'manager_name' => $this->manager?->name,
      'created_at' => $this->created_at->toIso8601String(),
      'created_at_formatted' => $this->created_at->format('d.m.Y H:i'),
      'pdf_url' => url("/api/v1/orders/{$this->code}/pdf"),
    ];
  }
}
