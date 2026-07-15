<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Nicole\Box\Core\Models\BindingRule;

readonly class BindingRuleExportDto
{
    public function __construct(
        public int $id,
        public string $parentType,
        public int $parentId,
        public string $childType,
        public int $childId,
        public ?array $conditions,
        public string $quantityFormula,
        public bool $isRequired,
    ) {}

    public static function fromModel(BindingRule $rule): self
    {
        return new self(
            id: $rule->id,
            parentType: $rule->parent_type,
            parentId: $rule->parent_id,
            childType: $rule->child_type,
            childId: $rule->child_id,
            conditions: $rule->conditions,
            quantityFormula: $rule->quantity_formula,
            isRequired: $rule->is_required,
        );
    }
}
