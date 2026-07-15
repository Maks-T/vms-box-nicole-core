<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    /**
     * Макрос для миграций: добавляет стандартную JSONB колонку настроек.
     * Использование в миграции: $table->settings();
     */
    Blueprint::macro('settings', function () {
      /** @var Blueprint $this */
      return $this->jsonb('settings')->nullable();
    });
  }
}
