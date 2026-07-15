<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

use Illuminate\Support\Facades\File;

class WidgetAssetHelper
{
  /**
   * Получение путей к JS и CSS файлам из манифеста конкретного виджета.
   *
   * @param string $widgetSlug Имя папки виджета в директории public (например, 'calculator-app')
   * @return array{js: string|null, css: string|null}
   */
  public static function getAssets(string $widgetSlug): array
  {
    $manifestPath = public_path($widgetSlug . '/manifest.json');

    if (!File::exists($manifestPath)) {
      return ['js' => null, 'css' => null];
    }

    $manifest = json_decode(File::get($manifestPath), true);

    return [
      'js' => self::findAsset($manifest, 'js'),
      'css' => self::findAsset($manifest, 'css'),
    ];
  }

  /**
   * Склеивание локальных путей Laravel с относительными путями манифеста.
   */
  private static function findAsset(array $manifest, string $extension): ?string
  {
    foreach ($manifest as $key => $path) {
      if (
        str_ends_with($key, ".{$extension}") &&
        (str_starts_with($key, 'main') || str_starts_with($key, 'index'))
      ) {
        return $path;
      }
    }

    return null;
  }

}
