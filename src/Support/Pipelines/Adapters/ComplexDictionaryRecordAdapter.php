<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

class ComplexDictionaryRecordAdapter extends BasePipelineEntityAdapter
{
  public function getLabel(): string
  {
    return ET::label(ET::COMPLEX_DICTIONARY_RECORD);
  }

  public function getModelClass(): string
  {
    return ComplexDictionaryRecord::class;
  }

  public function getParentId(Model $entity): ?int
  {
    /** @var ComplexDictionaryRecord $entity */
    return $entity->dictionary_id ? (int)$entity->dictionary_id : null;
  }

  public function getTypeCode(Model $entity): string
  {
    /** @var ComplexDictionaryRecord $entity */
    return $entity->dictionary?->code ?? 'dictionary_record';
  }

  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array
  {
    return ComplexDictionaryRecord::query()
      ->when($filterTypeCode, fn($q) => $q->whereHas('dictionary', fn($d) => $d->where('code', $filterTypeCode)))
      ->where('is_active', true)
      ->get()
      ->mapWithKeys(function (ComplexDictionaryRecord $r) use ($configuredIds) {
        $name = (string)$r->name;
        if (in_array((int)$r->id, $configuredIds, true)) {
          $name .= ' (' . __('Already in chain') . ')';
        }
        return [$r->id => $name];
      })
      ->toArray();
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
