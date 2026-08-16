<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class PipelineInputDto implements Arrayable, JsonSerializable
{
  /**
   * @param array<int, PipelineItemDto> $items
   * @param array<string, mixed> $context
   */
  public function __construct(
    public array $items,
    public array $context = [],
  ) {}

  public function toArray(): array
  {
    return [
      'items' => array_map(fn ($item) => $item->toArray(), $this->items),
      'context' => $this->context,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
