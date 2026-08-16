<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Nicole\Box\Core\Models\BindingRule;

readonly class BindingRuleExportDto implements Arrayable, JsonSerializable
{
  public function __construct(
    public int     $id,
    public string  $parentType,
    public int     $parentId,
    public ?string $childType = null,
    public ?int    $childId = null,
    public ?string $role = null,
    public ?array  $conditions = null,
    public ?array  $staticMeta = null,
    public string  $quantityFormula = '1',
    public bool    $isRequired = false,
  )
  {
  }

  public static function fromModel(BindingRule $rule): self
  {
    return new self(
      id: (int)$rule->id,
      parentType: (string)$rule->parent_type,
      parentId: (int)$rule->parent_id,
      childType: $rule->child_type ? (string)$rule->child_type : null,
      childId: $rule->child_id ? (int)$rule->child_id : null,
      role: $rule->role,
      conditions: $rule->conditions,
      staticMeta: $rule->static_meta,
      quantityFormula: (string)($rule->quantity_formula ?? '1'),
      isRequired: (bool)$rule->is_required,
    );
  }

  public function toArray(): array
  {
    return [
      'id' => $this->id,
      'parent_type' => $this->parentType,
      'parent_id' => $this->parentId,
      'child_type' => $this->childType,
      'child_id' => $this->childId,
      'role' => $this->role,
      'conditions' => $this->conditions,
      'static_meta' => $this->staticMeta,
      'quantity_formula' => $this->quantityFormula,
      'is_required' => $this->isRequired,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
