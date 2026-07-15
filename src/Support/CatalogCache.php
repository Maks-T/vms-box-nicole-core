<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\TaggableStore;
use Nicole\Box\Core\Support\Constants\CacheKey;

class CatalogCache
{
  /**
   * Умное кэширование каталога.
   */
  public static function remember(string $key, int $ttl, \Closure $callback)
  {
    $store = Cache::getStore();

    if ($store instanceof TaggableStore) {
      return Cache::tags([CacheKey::CATALOG_TAG])->remember($key, $ttl, $callback);
    }

    $version = Cache::get(CacheKey::CATALOG_VERSION, 1);
    $versionedKey = "v{$version}_{$key}";

    return Cache::remember($versionedKey, $ttl, $callback);
  }

  /**
   * Сброс (инвалидация) всего кэша каталога.
   */
  public static function invalidate(): void
  {
    $store = Cache::getStore();

    if ($store instanceof TaggableStore) {
      Cache::tags([CacheKey::CATALOG_TAG])->flush();
    } else {
      Cache::increment(CacheKey::CATALOG_VERSION);
    }
  }

}
