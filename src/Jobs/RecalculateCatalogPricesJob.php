<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nicole\Box\Core\Models\Product;

class RecalculateCatalogPricesJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @param string $currencyCode Код изменившейся валюты (например, 'USD')
   */
  public function __construct(
    public string $currencyCode
  ) {}

  /**
   * Выполнить фоновую задачу.
   */
  public function handle(): void
  {
    Product::query()
      ->where('is_active', true)
      ->chunk(100, function ($products) {
        foreach ($products as $product) {
          $product->refreshMinPrice();
        }
      });
  }

}
