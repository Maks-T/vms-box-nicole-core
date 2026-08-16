<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Contracts;

interface PipelineNodeInterface
{
  public function getPipelineParentId(): ?int;
  public function getPipelineName(string $locale): string;
  public function getPipelineSlug(): ?string;
  public function getPipelineImageUrl(): ?string;
}
