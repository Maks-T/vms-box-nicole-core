<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Nicole\Box\Core\CoreConfig;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Models\Media;
use Nicole\Box\Core\Support\Media\NicolePathGenerator;

class NicoleCoreServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    // Поднимаемся на два уровня вверх до корня пакета
    $this->loadJsonTranslationsFrom(realpath(__DIR__ . '/../../lang'));

    $this->mergeConfigFrom(__DIR__ . '/../../config/nicole.php', 'nicole');
    $this->mergeConfigFrom(__DIR__ . '/../../config/media-library.php', 'media-library');

    $this->app->singleton(PricingManager::class, fn() => new PricingManager);
    $this->app->singleton(CoreConfig::class, fn() => new CoreConfig());

    // Регистрируем дочерние провайдеры
    $this->app->register(MacroServiceProvider::class);
    $this->app->register(RelationServiceProvider::class);
    $this->app->register(ObserverServiceProvider::class);
    $this->app->register(RouteServiceProvider::class);
    $this->app->register(InertiaServiceProvider::class);
    $this->app->register(FilamentServiceProvider::class);
  }

  public function boot(): void
  {
    // Здесь также поднимаемся на два уровня вверх
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'nicole-core');

    if ($this->app->runningInConsole()) {
      $this->commands([
        \Nicole\Box\Core\Console\Commands\ImportCatalogCommand::class,
        \Nicole\Box\Core\Console\Commands\DbOptimizeCommand::class,
      ]);

      $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

      $this->publishes([__DIR__ . '/../../config/nicole.php' => config_path('nicole.php')], 'nicole-config');
      $this->publishes([__DIR__ . '/../../config/media-library.php' => config_path('media-library.php')], 'nicole-media-config');

      config([
        'media-library.media_model' => Media::class,
        'media-library.path_generator' => NicolePathGenerator::class,
      ]);
    }
  }
}
