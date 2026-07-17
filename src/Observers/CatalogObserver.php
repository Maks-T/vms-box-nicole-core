<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Observers;

use Illuminate\Support\Facades\Cache;

class CatalogObserver
{
  public function saved(): void
  {
    Cache::forget('vms_dashboard_stats');
    Cache::increment('catalog_version');
  }

  public function deleted(): void
  {
    Cache::forget('vms_dashboard_stats');
    Cache::increment('catalog_version');
  }
}
