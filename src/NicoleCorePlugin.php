<?php

declare(strict_types=1);

namespace Nicole\Box\Core;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Outerweb\FilamentTranslatableFields\TranslatableFieldsPlugin;

/**
 * Главный плагин ядра Nicole Core для Filament 5.
 * Инкапсулирует ресурсы, языковые настройки, системные плагины и рендер-хуки.
 *
 * @since 2026-09-06
 */
class NicoleCorePlugin implements Plugin
{
  public function getId(): string
  {
    return 'nicole-box-core';
  }

  public function register(Panel $panel): void
  {
    $panel->discoverResources(
      in: __DIR__ . '/Filament/Resources',
      for: 'Nicole\\Box\\Core\\Filament\\Resources',
    );

    $panel->discoverPages(
      in: __DIR__ . '/Filament/Pages',
      for: 'Nicole\\Box\\Core\\Filament\\Pages',
    );

    $panel->discoverClusters(
      in: __DIR__ . '/Filament/Clusters',
      for: 'Nicole\\Box\\Core\\Filament\\Clusters',
    );

    $panel->discoverWidgets(
      in: __DIR__ . '/Filament/Widgets',
      for: 'Nicole\\Box\\Core\\Filament\\Widgets',
    );

    $locales = config('nicole.locales', ['ru']);

    $panel->plugins([
      FilamentClearCachePlugin::make(),
      FilamentShieldPlugin::make()->navigationGroup(__('Access Control')),
      SpatieTranslatablePlugin::make()->defaultLocales($locales),
      TranslatableFieldsPlugin::make()->supportedLocales($locales),
    ]);

    $panel
      ->renderHook(
        PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
        fn () => view('nicole-core::filament.components.topbar-calculator-button')
      );
  }

  public function boot(Panel $panel): void
  {
    //
  }

  public static function make(): static
  {
    return new static;
  }

}