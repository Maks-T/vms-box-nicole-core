<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

class CategoryAdapter extends BasePipelineEntityAdapter
{
  public function getLabel(): string
  {
    return ET::label(ET::CATEGORY);
  }

  public function getModelClass(): string
  {
    return Category::class;
  }

  public function getParentId(Model $entity): ?int
  {
    /** @var Category $entity */
    return $entity->parent_id ? (int)$entity->parent_id : null;
  }

  public function getTypeCode(Model $entity): string
  {
    return 'category';
  }

  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array
  {
    return Category::where('is_active', true)->pluck('name', 'id')->toArray();
  }

  public function getFormComponent(string $fieldName = 'entity_id', ?string $filterTypeCode = null, bool $multiple = false): Field
  {
    return Select::make($fieldName)
      ->label($this->getLabel())
      ->required()
      ->multiple($multiple)
      ->searchable()
      ->preload()
      ->options(fn() => $this->getSelectOptions($filterTypeCode));
  }
}
