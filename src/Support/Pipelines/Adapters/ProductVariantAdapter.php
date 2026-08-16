<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines\Adapters;

use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Filament\Forms\Components\VariantSelect;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

class ProductVariantAdapter extends BasePipelineEntityAdapter
{
  public function getLabel(): string
  {
    return ET::label(ET::PRODUCT_VARIANT);
  }

  public function getModelClass(): string
  {
    return ProductVariant::class;
  }

  public function getParentId(Model $entity): ?int
  {
    /** @var ProductVariant $entity */
    return $entity->product_id ? (int)$entity->product_id : null;
  }

  public function getName(Model $entity, string $locale): string
  {
    /** @var ProductVariant $entity */
    $variantName = $entity->getTranslation('name', $locale);
    if (!empty($variantName)) {
      return $variantName;
    }
    return $entity->product?->getTranslation('name', $locale) ?: ($entity->name ?? $entity->sku);
  }

  public function getTypeCode(Model $entity): string
  {
    /** @var ProductVariant $entity */
    return $entity->product?->type?->code ?? 'general';
  }

  public function getSelectOptions(?string $filterTypeCode = null, array $configuredIds = []): array
  {
    return ProductVariant::query()
      ->when($filterTypeCode, fn($q) => $q->whereHas('product.type', fn($t) => $t->where('code', $filterTypeCode)))
      ->where('is_active', true)
      ->with(['product.media', 'media'])
      ->get()
      ->mapWithKeys(function (ProductVariant $v) use ($configuredIds) {
        $html = VariantSelect::renderVariantOption($v);
        if (in_array((int)$v->id, $configuredIds, true)) {
          $html = $this->renderConfiguredBadge($html);
        }
        return [$v->id => $html];
      })
      ->toArray();
  }
}
