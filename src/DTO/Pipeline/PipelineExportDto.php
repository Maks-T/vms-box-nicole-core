<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Support\Collection;
use Nicole\Box\Core\Models\Pipeline;

readonly class PipelineExportDto
{
    /**
     * @param  Collection<int, \core\src\DTO\Pipeline\BindingRuleExportDto>  $rules
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $industry,
        public Collection $rules,
    ) {}

    public static function fromModel(Pipeline $pipeline): self
    {
        $rules = $pipeline->rules->map(
            fn ($rule) => BindingRuleExportDto::fromModel($rule),
        );

        return new self(
            id: $pipeline->id,
            name: $pipeline->name,
            industry: $pipeline->industry,
            rules: $rules,
        );
    }
}
