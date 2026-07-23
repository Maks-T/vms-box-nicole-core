<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Трейт HasNicoleMedia управляет загрузкой, конвертацией и получением
 * ссылок на изображения для сущностей каталога.
 */
trait HasNicoleMedia
{
  use InteractsWithMedia;

  /**
   * Регистрация автоматических конвертаций изображений.
   */
  public function registerMediaConversions(?Media $media = null): void
  {
    if ($media && $media->getCustomProperty('skip_conversions')) {
      return;
    }

    $this->addMediaConversion('preview')
      ->fit(Fit::Max, 400, 400)
      ->format('webp')
      ->quality(80)
      ->sharpen(10)
      ->nonQueued()
      ->performOnCollections('main');
  }

  /**
   * Получить URL превью-изображения динамически по зарегистрированным коллекциям
   *
   * @return string|null Возвращает абсолютный URL изображения или null
   */
  public function getPreviewUrl(): ?string
  {
    $url = null;

    $collections = collect($this->getRegisteredMediaCollections())
      ->sortBy(function ($collection) {
        return match ($collection->name) {
          'preview' => 1,
          'main'    => 2,
          'drawing' => 3,
          default   => 10,
        };
      });

    foreach ($collections as $collection) {
      if ($this->hasMedia($collection->name)) {
        $url = $this->getFirstMediaUrl($collection->name, 'preview')
          ?: $this->getFirstMediaUrl($collection->name);

        if ($url) {
          break;
        }
      }
    }

    // Каскад снизу вверх (только для Базового товара)
    if (empty($url) && $this->relationLoaded('variants')) {
      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $this->variants
        ->where('is_active', true)
        ->sortByDesc('is_default')
        ->first();

      if ($defaultVariant) {
        return $defaultVariant->getPreviewUrl();
      }
    }

    if (empty($url)) {
      return null;
    }

    return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

  /**
   * Получить URL детального изображения динамически по зарегистрированным коллекциям.
   *
   * @return string|null Возвращает абсолютный URL детального изображения или null
   */
  public function getDetailUrl(): ?string
  {
    $url = null;

    $collections = collect($this->getRegisteredMediaCollections())
      ->sortBy(function ($collection) {
        return match ($collection->name) {
          'main'    => 1,
          'drawing' => 2,
          default   => 10,
        };
      });

    foreach ($collections as $collection) {
      if ($this->hasMedia($collection->name)) {
        $url = $this->getFirstMediaUrl($collection->name);

        if ($url) {
          break;
        }
      }
    }

    // Каскад снизу вверх (только для Базового товара)
    if (empty($url) && $this->relationLoaded('variants')) {
      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $this->variants
        ->where('is_active', true)
        ->sortByDesc('is_default')
        ->first();

      if ($defaultVariant) {
        return $defaultVariant->getDetailUrl();
      }
    }

    if (empty($url)) {
      return null;
    }

    return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

}
