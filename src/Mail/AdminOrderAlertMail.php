<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Nicole\Box\Core\Models\Order;

/**
 * Письмо-уведомление администраторам и менеджерам о новом расчете/заявке.
 *
 * @since 2026-10-08
 */
class AdminOrderAlertMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public int $tries = 3;
  public int $timeout = 60;
  public int $backoff = 10;

  public function __construct(
    public Order $order
  ) {}

  public function envelope(): Envelope
  {
    $orderNumber = $this->order->code;

    return new Envelope(
      subject: "Заявка с конфигуратора душевых (№{$orderNumber})",
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'nicole-core::emails.admin.order-submitted',
    );
  }
}