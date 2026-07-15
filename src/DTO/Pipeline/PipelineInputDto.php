<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

readonly class PipelineInputDto
{
    /**
     * @param  array<int, PipelineItemDto>  $items
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public array $items,
        public array $context = [],
    ) {}
}
