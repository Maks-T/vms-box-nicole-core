<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Nicole\Box\Core\Support\Constants\MediaCollection;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasNicoleMedia
{
  use InteractsWithMedia;

  public function registerMediaConversions(?Media $media = null): void
  {
    // @since 2026-11-06: миниатюра для таблиц и селектов админки (кружки 40–60 px)
    $this->addMediaConversion(MediaCollection::THUMB)
      ->fit(Fit::Crop, 96, 96)
      ->format('webp')
      ->quality(75)
      ->nonQueued()
      ->performOnCollections(MediaCollection::MAIN, MediaCollection::PREVIEW);

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
   * Медиафайл превью сущности: своё фото (preview → main → drawing), а при $cascade —
   * фото дефолтной модификации товара или родительского товара модификации.
   * Единый каскад для getPreviewDiskPath() и getThumbUrl().
   *
   * Публичный: вызывается на связанной модели другого класса (Product ↔ ProductVariant).
   *
   * @since 2026-11-06
   */
  public function resolvePreviewMedia(bool $cascade = true): ?Media
  {
    $media = $this->getFirstMedia(MediaCollection::PREVIEW)
      ?? ($this->getFirstMedia(MediaCollection::MAIN)
        ?? $this->getFirstMedia('drawing'));

    if ($media || !$cascade) {
      return $media;
    }

    if ($this instanceof \Nicole\Box\Core\Models\Product) {
      $variants = $this->relationLoaded('variants')
        ? $this->variants
        : $this->variants()->where('is_active', true)->get();

      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $variants->sortByDesc('is_default')->first();

      if ($defaultVariant) {
        $variantMedia = $defaultVariant->resolvePreviewMedia(false);
        if ($variantMedia) {
          return $variantMedia;
        }
      }
    }

    if ($this instanceof \Nicole\Box\Core\Models\ProductVariant && $this->product) {
      return $this->product->resolvePreviewMedia(false);
    }

    return null;
  }

  public function getPreviewDiskPath(bool $cascade = true): ?string
  {
    return $this->resolvePreviewMedia($cascade)?->getPathRelativeToRoot();
  }

  public function getPreviewUrl(bool $cascade = true): ?string
  {
    $diskPath = $this->getPreviewDiskPath($cascade);

    return $diskPath ? $this->diskPathToUrl($diskPath) : null;
  }

  /**
   * URL миниатюры 96×96 (конверсия thumb) для таблиц и селектов админки.
   * Если миниатюра ещё не создана (файлы, загруженные до появления конверсии, —
   * до media-library:regenerate), возвращает оригинал, как getPreviewUrl().
   *
   * @since 2026-11-06
   */
  public function getThumbUrl(bool $cascade = true): ?string
  {
    $media = $this->resolvePreviewMedia($cascade);

    if (!$media) {
      return null;
    }

    $diskPath = $media->hasGeneratedConversion(MediaCollection::THUMB)
      ? $media->getPathRelativeToRoot(MediaCollection::THUMB)
      : $media->getPathRelativeToRoot();

    return $this->diskPathToUrl($diskPath);
  }

  /**
   * Публичный URL файла на диске public относительно APP_URL.
   *
   * @since 2026-11-06
   */
  protected function diskPathToUrl(string $diskPath): string
  {
    $url = Storage::disk('public')->url($diskPath);

    return rtrim((string)config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

  public function getDetailUrl(bool $cascade = true): ?string
  {
    $media = $this->getFirstMedia('main')
      ?? ($this->getFirstMedia('drawing')
        ?? $this->getFirstMedia('preview'));

    if ($media) {
      return $this->diskPathToUrl($media->getPathRelativeToRoot());
    }

    if (!$cascade) {
      return null;
    }

    if ($this instanceof \Nicole\Box\Core\Models\Product) {
      $variants = $this->relationLoaded('variants')
        ? $this->variants
        : $this->variants()->where('is_active', true)->get();

      /** @var \Nicole\Box\Core\Models\ProductVariant|null $defaultVariant */
      $defaultVariant = $variants->sortByDesc('is_default')->first();

      if ($defaultVariant) {
        return $defaultVariant->getDetailUrl(false);
      }
    }

    if ($this instanceof \Nicole\Box\Core\Models\ProductVariant && $this->product) {
      return $this->product->getDetailUrl(false);
    }

    return null;
  }

}
