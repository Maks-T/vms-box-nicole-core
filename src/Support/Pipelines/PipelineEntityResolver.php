<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nicole\Box\Core\Contracts\PipelineNodeInterface;
use Nicole\Box\Core\Filament\Forms\Components\ProductSelect;
use Nicole\Box\Core\Filament\Forms\Components\VariantSelect;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

/**
 * Универсальный реестр извлечения метаданных, связей и опций сущностей пайплайна.
 */
class PipelineEntityResolver
{
  protected static array $resolvers = [];
  protected static array $entityTypes = [];

  public static function register(string $modelClass, array $resolvers): void
  {
    static::$resolvers[$modelClass] = array_merge(
      static::$resolvers[$modelClass] ?? [],
      $resolvers
    );
  }

  public static function registerEntityType(string $morphType, string $label): void
  {
    static::$entityTypes[$morphType] = $label;
  }

  public static function getAvailableEntityTypes(): array
  {
    $defaults = [
      (new ProductVariant())->getMorphClass() => __('Product Variant (SKU)'),
      (new Product())->getMorphClass() => __('Product'),
      (new ComplexDictionaryRecord())->getMorphClass() => __('Complex Dictionary Record'),
      (new Category())->getMorphClass() => __('Category'),
    ];

    return array_merge($defaults, static::$entityTypes);
  }

  /**
   * Динамическая подгрузка списка опций со стилизованным HTML и пометкой задействованных элементов.
   *
   * @param string $entityType
   * @param string|null $filterTypeCode
   * @param array<int> $configuredIds Список уже задействованных ID
   * @return array<int|string, string>
   */
  public static function getEntitySelectOptions(
    string  $entityType,
    ?string $filterTypeCode = null,
    array   $configuredIds = []
  ): array
  {
    $morphClass = Relation::getMorphedModel($entityType) ?? $entityType;
    $configuredIds = array_map('intval', $configuredIds);

    // 1. Варианты товара (SKU)
    if ($entityType === (new ProductVariant())->getMorphClass() || $morphClass === ProductVariant::class) {
      return ProductVariant::query()
        ->when($filterTypeCode, fn($q) => $q->whereHas('product.type', fn($t) => $t->where('code', $filterTypeCode)))
        ->where('is_active', true)
        ->with(['product.media', 'media'])
        ->get()
        ->mapWithKeys(function (ProductVariant $v) use ($configuredIds) {
          $isConfigured = in_array((int)$v->id, $configuredIds, true);
          $html = VariantSelect::renderVariantOption($v);

          if ($isConfigured) {
            $badge = "<span style='margin-left: 6px; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; background: #fee2e2; color: #dc2626; border-radius: 4px;'>Уже в цепочке</span>";
            $html = str_replace("ID: {$v->id}", "ID: {$v->id} {$badge}", $html);
            $html = "<div style='opacity: 0.5; filter: grayscale(80%);'>{$html}</div>";
          }

          return [$v->id => $html];
        })
        ->toArray();
    }

    // 2. Базовые товары
    if ($entityType === (new Product())->getMorphClass() || $morphClass === Product::class) {
      return Product::query()
        ->when($filterTypeCode, fn($q) => $q->whereHas('type', fn($t) => $t->where('code', $filterTypeCode)))
        ->where('is_active', true)
        ->with('media')
        ->get()
        ->mapWithKeys(function (Product $p) use ($configuredIds) {
          $isConfigured = in_array((int)$p->id, $configuredIds, true);
          $html = ProductSelect::renderProductOption($p);

          if ($isConfigured) {
            $badge = "<span style='margin-left: 6px; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; background: #fee2e2; color: #dc2626; border-radius: 4px;'>Уже в цепочке</span>";
            $html = str_replace("ID: {$p->id}", "ID: {$p->id} {$badge}", $html);
            $html = "<div style='opacity: 0.5; filter: grayscale(80%);'>{$html}</div>";
          }

          return [$p->id => $html];
        })
        ->toArray();
    }

    // 3. Записи умных справочников
    if ($entityType === (new ComplexDictionaryRecord())->getMorphClass() || $morphClass === ComplexDictionaryRecord::class) {
      return ComplexDictionaryRecord::query()
        ->when($filterTypeCode, fn($q) => $q->whereHas('dictionary', fn($d) => $d->where('code', $filterTypeCode)))
        ->where('is_active', true)
        ->get()
        ->mapWithKeys(function (ComplexDictionaryRecord $r) use ($configuredIds) {
          $isConfigured = in_array((int)$r->id, $configuredIds, true);
          $label = (string)$r->name;
          if ($isConfigured) {
            $label .= ' (Уже в цепочке)';
          }
          return [$r->id => $label];
        })
        ->toArray();
    }

    // 4. Категории
    if ($entityType === (new Category())->getMorphClass() || $morphClass === Category::class) {
      return Category::where('is_active', true)->pluck('name', 'id')->toArray();
    }

    return [];
  }

  public static function resolveParentId(Model $entity): ?int
  {
    if ($entity instanceof PipelineNodeInterface) {
      return $entity->getPipelineParentId();
    }

    $class = get_class($entity);
    if (isset(static::$resolvers[$class]['parent_id'])) {
      $result = call_user_func(static::$resolvers[$class]['parent_id'], $entity);
      return $result !== null ? (int)$result : null;
    }

    return match (true) {
      $entity instanceof ProductVariant => $entity->product_id,
      $entity instanceof ComplexDictionaryRecord => $entity->dictionary_id,
      $entity instanceof Category => $entity->parent_id,
      $entity instanceof Product => $entity->category_id,
      default => isset($entity->parent_id) ? (int)$entity->parent_id : null,
    };
  }

  public static function resolveName(Model $entity, string $locale): string
  {
    if ($entity instanceof PipelineNodeInterface) {
      return $entity->getPipelineName($locale);
    }

    $class = get_class($entity);
    if (isset(static::$resolvers[$class]['name'])) {
      return (string)call_user_func(static::$resolvers[$class]['name'], $entity, $locale);
    }

    if ($entity instanceof ProductVariant && empty($entity->getTranslation('name', $locale)) && $entity->product) {
      return (string)($entity->product->getTranslation('name', $locale) ?: $entity->product->name);
    }

    $name = null;
    if (method_exists($entity, 'getTranslation')) {
      $name = $entity->getTranslation('name', $locale) ?: $entity->getTranslation('title', $locale);
    }
    $name ??= $entity->name ?? ($entity->title ?? ($entity->sku ?? ($entity->code ?? ('ID #' . $entity->getKey()))));

    return (string)$name;
  }

  public static function resolveSlug(Model $entity): ?string
  {
    if ($entity instanceof PipelineNodeInterface) {
      return $entity->getPipelineSlug();
    }

    $class = get_class($entity);
    if (isset(static::$resolvers[$class]['slug'])) {
      return call_user_func(static::$resolvers[$class]['slug'], $entity);
    }

    return $entity->slug ?? ($entity->product?->slug ?? null);
  }

  public static function resolveImageUrl(Model $entity): ?string
  {
    if ($entity instanceof PipelineNodeInterface) {
      return $entity->getPipelineImageUrl();
    }

    $class = get_class($entity);
    if (isset(static::$resolvers[$class]['image_url'])) {
      return call_user_func(static::$resolvers[$class]['image_url'], $entity);
    }

    if (method_exists($entity, 'getPreviewUrl')) {
      return $entity->getPreviewUrl();
    }
    if (isset($entity->product) && method_exists($entity->product, 'getPreviewUrl')) {
      return $entity->product->getPreviewUrl();
    }
    if (method_exists($entity, 'getFirstMediaUrl')) {
      return $entity->getFirstMediaUrl('main') ?: null;
    }

    return null;
  }

  public static function resolveTypeCode(Model $entity): string
  {
    $class = get_class($entity);
    if (isset(static::$resolvers[$class]['type_code'])) {
      return (string)call_user_func(static::$resolvers[$class]['type_code'], $entity);
    }

    if ($entity instanceof ProductVariant) {
      return $entity->product?->type?->code ?? 'general';
    }
    if ($entity instanceof Product) {
      return $entity->type?->code ?? 'general';
    }
    if (isset($entity->type) && is_object($entity->type) && isset($entity->type->code)) {
      return (string)$entity->type->code;
    }

    return (string)($entity->code ?? $entity->getMorphClass());
  }
}
