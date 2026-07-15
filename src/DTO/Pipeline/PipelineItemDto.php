<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

readonly class PipelineItemDto
{
    public function __construct(
        public int $variantId,
        public float $quantity,
        public ?int $parentNodeId = null,
    ) {}
}
