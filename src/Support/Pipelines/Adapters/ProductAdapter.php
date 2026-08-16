<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

class ProductAdapter extends BasePipelineEntityAdapter
{
  public function getLabel(): string
  {
    return ET::label(ET::PRODUCT);
  }

  public function getModelClass(): string
  {
    return Product::class;
  }

  public function getParentId(Model $entity): ?int
  {
    /** @var Product $entity */
    return $entity->category_id ? (int)$entity->category_id : null;
  }

  public function getTypeCode(Model $entity): string
  {
    /** @var Product $entity */
    return $entity->type?->code ?? 'general';
  }

  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array
  {
    return Product::query()
      ->when($filterTypeCode, fn($q) => $q->whereHas('type', fn($t) => $t->where('code', $filterTypeCode)))
      ->where('is_active', true)
      ->with('media')
      ->get()
      ->mapWithKeys(function (Product $p) use ($configuredIds) {
        $html = ProductSelect::renderProductOption($p);
        if (in_array((int)$p->id, $configuredIds, true)) {
          $html = $this->renderConfiguredBadge($html);
        }
        return [$p->id => $html];
      })
      ->toArray();
  }
}
