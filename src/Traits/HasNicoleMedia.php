<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Трейт HasNicoleMedia управляет загрузкой, конвертацией и получением
 * ссылок на изображения для сущностей каталога (базовых товаров и модификаций SKU).
 *
 * --------------------------------------------------
 * Бизнес-правила каскада и наследования изображений:
 *
 * 1. Модификация товара (ProductVariant / SKU):
 *    - Возвращает только собственные медиафайлы.
 *    - Если у модификации нет собственного фото - метод возвращает null.
 *    - [ПРАВИЛО]: Прямое наследование сверху вниз (от базового товара к SKU)
 *      отсутствует, так как у разных SKU одного товара (например, разные цвета)
 *      не должно быть одинакового фото, если они не заданы явно.
 *
 * 2. Базовый товар (Product):
 *    - В первую очередь возвращает собственное превью/детальное фото.
 *    - Если собственного фото нет, а связь 'variants' загружена в память (Eager Loaded),
 *      товар автоматически наследует фото снизу вверх - берет изображение
 *      у своей активной дефолтной модификации (SKU).
 * --------------------------------------------------
 */
trait HasNicoleMedia
{
  use InteractsWithMedia;

  /**
   * Регистрация автоматических конвертаций изображений.
   * Оптимизирует исходные изображения под WebP формат для быстрого рендеринга в UI.
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
   * Получить URL превью-изображения (Thumbnail).
   *
   * @return string|null Возвращает абсолютный URL изображения или null
   */
  public function getPreviewUrl(): ?string
  {
    $url = null;

    // Шаг 1: Проверяем собственные медиафайлы текущей модели (будь то продукт или вариант)
    if ($this->hasMedia('preview')) {
      $url = $this->getFirstMediaUrl('preview');
    } elseif ($this->hasMedia('main')) {
      $url = $this->getFirstMediaUrl('main', 'preview') ?:
        $this->getFirstMediaUrl('main');
    }

    // Шаг 2: Каскад снизу вверх (только для Базового товара).
    // Если у товара нет фото, и связь вариантов загружена в память - берем фото у дефолтного SKU.
    if (empty($url) && $this->relationLoaded('variants')) {
      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $this->variants
        ->where('is_active', true)
        ->sortByDesc('is_default') // Фильтруем коллекцию в оперативной памяти PHP
        ->first();

      if ($defaultVariant) {
        return $defaultVariant->getPreviewUrl(); // Вызываем получение у варианта напрямую
      }
    }

    if (empty($url)) {
      return null;
    }

    // Формируем корректный абсолютный URL с учетом домена приложения
    return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

  /**
   * Получить URL детального изображения (High-Res).
   *
   * @return string|null Возвращает абсолютный URL детального изображения или null
   */
  public function getDetailUrl(): ?string
  {
    $url = null;

    // Шаг 1: Проверяем собственные детальные медиафайлы текущей модели
    if ($this->hasMedia('main')) {
      $url = $this->getFirstMediaUrl('main');
    }

    // Шаг 2: Каскад снизу вверх (только для Базового товара).
    // Если у товара нет фото, и связь вариантов загружена - берем детальное фото у дефолтного SKU.
    if (empty($url) && $this->relationLoaded('variants')) {
      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $this->variants
        ->where('is_active', true)
        ->sortByDesc('is_default') // Фильтруем коллекцию в оперативной памяти PHP
        ->first();

      if ($defaultVariant) {
        return $defaultVariant->getDetailUrl(); // Вызываем получение у варианта напрямую
      }
    }

    if (empty($url)) {
      return null;
    }

    // Формируем корректный абсолютный URL с учетом домена приложения
    return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

}
