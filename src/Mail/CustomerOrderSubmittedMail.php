<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Nicole\Box\Core\Models\Order;

/**
 * Письмо-подтверждение клиенту об успешном принятии заявки с прикреплением КП.
 *
 * @since 2026-10-08
 */
class CustomerOrderSubmittedMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public int $tries = 3;
  public int $timeout = 60;
  public int $backoff = 10;

  public function __construct(
    public Order $order,
    public ?string $pdfBinary = null
  ) {}

  public function envelope(): Envelope
  {
    $fromName = config('mail.from.name', 'АМИГРУПП');

    return new Envelope(
      subject: "Ваш расчет №{$this->order->code} принят | {$fromName}",
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'nicole-core::emails.customer.order-submitted',
    );
  }

  public function attachments(): array
  {
    if ($this->pdfBinary) {
      return [
        Attachment::fromData(fn () => $this->pdfBinary, "КП_Заказ_{$this->order->code}.pdf")
          ->withMime('application/pdf'),
      ];
    }
    return [];
  }
}