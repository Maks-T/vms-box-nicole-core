<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class PipelineItemDto implements Arrayable, JsonSerializable
{
  public function __construct(
    public int $id,
    public float $quantity,
    public string $type = 'product_variant',
    public ?int $parentNodeId = null,
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      id: (int) ($data['id'] ?? 0),
      quantity: (float) ($data['quantity'] ?? 1.0),
      type: (string) ($data['type'] ?? 'product_variant'),
      parentNodeId: isset($data['parent_node_id']) ? (int) $data['parent_node_id'] : null,
    );
  }

  public function toArray(): array
  {
    return [
      'type' => $this->type,
      'id' => $this->id,
      'quantity' => $this->quantity,
      'parent_node_id' => $this->parentNodeId,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
