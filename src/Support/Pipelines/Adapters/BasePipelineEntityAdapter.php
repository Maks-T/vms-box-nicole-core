<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Illuminate\Database\Eloquent\Model;

abstract class BasePipelineEntityAdapter implements PipelineEntityAdapterInterface
{
  public function getMorphType(): string
  {
    $modelClass = $this->getModelClass();
    return (new $modelClass())->getMorphClass();
  }

  public function getParentId(Model $entity): ?int
  {
    return isset($entity->parent_id) ? (int)$entity->parent_id : null;
  }

  public function getName(Model $entity, string $locale): string
  {
    $name = null;
    if (method_exists($entity, 'getTranslation')) {
      $name = $entity->getTranslation('name', $locale) ?: $entity->getTranslation('title', $locale);
    }
    return (string)($name ?? ($entity->name ?? ($entity->title ?? ($entity->sku ?? ('ID #' . $entity->getKey())))));
  }

  public function getSlug(Model $entity): ?string
  {
    return $entity->slug ?? ($entity->product?->slug ?? null);
  }

  public function getImageUrl(Model $entity): ?string
  {
    if (method_exists($entity, 'getPreviewUrl')) {
      return $entity->getPreviewUrl();
    }
    if (isset($entity->product) && method_exists($entity->product, 'getPreviewUrl')) {
      return $entity->product->getPreviewUrl();
    }
    return null;
  }

  public function getTypeCode(Model $entity): string
  {
    return (string)($entity->code ?? $entity->getMorphClass());
  }

  /**
   * Вспомогательный метод добавления бейджа "Уже в цепочке".
   */
  protected function renderConfiguredBadge(string $html): string
  {
    $badge = "<span style='margin-left: 6px; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; background: #fee2e2; color: #dc2626; border-radius: 4px;'>Уже в цепочке</span>";
    $withBadge = preg_replace('/(ID:\s*\d+)/', '$1 ' . $badge, $html);
    return "<div style='opacity: 0.5; filter: grayscale(80%);'>{$withBadge}</div>";
  }
}
