<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Custom path generator for Box (Cloud SaaS) Core.
 * Generates beautiful, human-readable semantic paths.
 */
class NicolePathGenerator implements PathGenerator
{
    /**
     * Get the specific path for the given media.
     * Format: catalog/{model_basename}/{model_id}/{collection_name}/
     */
    public function getPath(Media $media): string
    {
        $entityName = strtolower(class_basename($media->model_type));

        return 'catalog/'.
          $entityName.
          '/'.
          $media->model_id.
          '/'.
          $media->collection_name.
          '/';
    }

    /**
     * Get the path for conversions of the given media.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    /**
     * Get the path for responsive images of the given media.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}
