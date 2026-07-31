<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasNicoleMedia
{
  use InteractsWithMedia;

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

  public function getPreviewDiskPath(bool $cascade = true): ?string
  {
    $media = $this->getFirstMedia('preview')
      ?? ($this->getFirstMedia('main')
        ?? $this->getFirstMedia('drawing'));

    if ($media) {
      return $media->getPathRelativeToRoot();
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

      if ($defaultVariant && $defaultVariant->hasMedia()) {
        return $defaultVariant->getPreviewDiskPath(false);
      }
    }

    if ($this instanceof \Nicole\Box\Core\Models\ProductVariant && $this->product) {
      return $this->product->getPreviewDiskPath(false);
    }

    return null;
  }

  public function getPreviewUrl(bool $cascade = true): ?string
  {
    $diskPath = $this->getPreviewDiskPath($cascade);

    if (!$diskPath) {
      return null;
    }

    $url = \Illuminate\Support\Facades\Storage::disk('public')->url($diskPath);

    return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
  }

  public function getDetailUrl(bool $cascade = true): ?string
  {
    $media = $this->getFirstMedia('main')
      ?? ($this->getFirstMedia('drawing')
        ?? $this->getFirstMedia('preview'));

    if ($media) {
      $url = \Illuminate\Support\Facades\Storage::disk('public')->url($media->getPathRelativeToRoot());
      return rtrim(config('app.url'), '/') . parse_url($url, PHP_URL_PATH);
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