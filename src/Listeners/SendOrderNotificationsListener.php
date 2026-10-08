<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Nicole\Box\Core\Events\OrderCheckoutSubmitted;
use Nicole\Box\Core\Mail\AdminOrderAlertMail;
use Nicole\Box\Core\Mail\CustomerOrderSubmittedMail;

/**
 * Фоновый обработчик отправки писем при оформлении заявки через очередь Redis.
 *
 * @since 2026-10-08
 */
class SendOrderNotificationsListener implements ShouldQueue
{
  use InteractsWithQueue;

  public bool $afterCommit = true;
  public string $connection = 'redis';
  public string $queue = 'emails';

  public int $tries = 3;
  public int $timeout = 60;
  public int $backoff = 10;

  public function handle(OrderCheckoutSubmitted $event): void
  {
    $order = $event->order;
    $order->loadMissing(['customer', 'sections']);

    // 1. Отправка Алексею / Администраторам на почту из ADMIN_EMAILS
    $adminEmailsRaw = env('ADMIN_EMAILS', config('mail.admin_emails'));
    if ($adminEmailsRaw) {
      $adminEmails = array_filter(array_map('trim', explode(',', (string)$adminEmailsRaw)));
      if (!empty($adminEmails)) {
        try {
          Mail::to($adminEmails)->send(new AdminOrderAlertMail($order));
          Log::info("Notification sent to staff for order #{$order->code}", ['recipients' => $adminEmails]);
        } catch (\Throwable $e) {
          Log::error("Failed to send staff email for order #{$order->code}: " . $e->getMessage());
        }
      }
    }

    // 2. Отправка подтверждения клиенту (если он указал свой email)
    $customerEmail = $order->customer?->email;
    if ($customerEmail) {
      try {
        Mail::to($customerEmail)->send(new CustomerOrderSubmittedMail($order));
        Log::info("Confirmation email sent to customer for order #{$order->code}", ['email' => $customerEmail]);
      } catch (\Throwable $e) {
        Log::error("Failed to send customer email for order #{$order->code}: " . $e->getMessage());
      }
    }
  }
}