<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    $this->registerApiRoutes();
    $this->registerWebRoutes();
  }

  protected function registerApiRoutes(): void
  {
    if (!$this->app->routesAreCached()) {
      Route::prefix('api/v1')
        ->middleware(['api', \Nicole\Box\Core\Http\Middleware\EnforceChannelContext::class])
        ->group(__DIR__ . '/../../routes/api.php');
    }
  }

  /**
   * Веб-маршруты ядра - дефолтные реализации.
   *
   * @since 2026-11-06: регистрируются после загрузки всех провайдеров и только если
   * отраслевой модуль не объявил маршрут с тем же именем.
   */
  protected function registerWebRoutes(): void
  {
    if ($this->app->routesAreCached()) {
      return;
    }

    $this->app->booted(function (): void {
      $routes = Route::getRoutes();
      $routes->refreshNameLookups();

      if ($routes->hasNamedRoute('calculator.show')) {
        return;
      }

      Route::middleware(['web'])
        ->group(__DIR__ . '/../../routes/web.php');
    });
  }
}
