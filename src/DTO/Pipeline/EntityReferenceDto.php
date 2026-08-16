<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

readonly class EntityReferenceDto implements Arrayable, JsonSerializable
{
  public function __construct(
    public string  $type,
    public int     $id,
    public ?int    $parentId = null,
    public string  $name = '',
    public ?string $slug = null,
    public ?string $imageUrl = null,
  )
  {
  }

  /**
   * Фабричный метод создания DTO напрямую из модели через универсальный резолвер.
   */
  public static function fromModel(?Model $entity, ?string $locale = null): ?self
  {
    if (!$entity) {
      return null;
    }

    $locale ??= app()->getLocale();

    return new self(
      type: $entity->getMorphClass(),
      id: (int)$entity->getKey(),
      parentId: PipelineEntityResolver::resolveParentId($entity),
      name: PipelineEntityResolver::resolveName($entity, $locale),
      slug: PipelineEntityResolver::resolveSlug($entity),
      imageUrl: PipelineEntityResolver::resolveImageUrl($entity),
    );
  }

  /**
   * Приведение к массиву для JSON API и Blade-шаблонов.
   *
   * @return array{type: string, id: int, parent_id: int|null, name: string, slug: string|null, image_url: string|null}
   */
  public function toArray(): array
  {
    return [
      'type' => $this->type,
      'id' => $this->id,
      'parent_id' => $this->parentId,
      'name' => $this->name,
      'slug' => $this->slug,
      'image_url' => $this->imageUrl,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }

}
