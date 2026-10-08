<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nicole\Box\Core\Models\Order;

/**
 * Событие оформления заказа через форму заявки с контактными данными клиента.
 *
 * @since 2026-10-08
 */
class OrderCheckoutSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Order $order Экземпляр сохраненного заказа
     * @param array<string, mixed> $rawPayload Исходный пакет входящих данных SaveData
     */
    public function __construct(
        public Order $order,
        public array $rawPayload = []
    ) {}
}