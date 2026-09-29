<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;
use Nicole\Box\Core\Support\CatalogCache;

/**
 * Сервис глубокого клонирования товаров и торговых модификаций (SKU).
 *
 * @since 2026-09-24
 */
class ReplicationService
{
  /**
   * Глубокое клонирование модификации товара (SKU).
   */
  public function replicateVariant(ProductVariant $variant, array $overrides = []): ProductVariant
  {
    return DB::transaction(function () use ($variant, $overrides) {
      // @since 2026-09-29: Генерация чистого уникального SKU через SkuGeneratorService
      $targetSku = $overrides['sku'] ?? $this->generateUniqueVariantSku((string) $variant->sku, $variant->product);

      $replicaData = array_merge($variant->only([
        'product_id',
        'price_group_id',
        'cost_price',
        'currency',
        'is_active',
        'is_manual_pricing',
        'sort_order',
        'settings',
      ]), [
        'sku'           => $targetSku,
        'external_code' => null,
        'is_default'    => false,
        'stock'         => 0.0,
      ]);

      if (isset($overrides['name'])) {
        $replicaData['name'] = $overrides['name'];
      } elseif (!empty($variant->name)) {
        $replicaData['name'] = $variant->getTranslations('name');
      }

      foreach ($overrides as $key => $value) {
        if (!in_array($key, ['sku', 'name'], true)) {
          $replicaData[$key] = $value;
        }
      }

      /** @var ProductVariant $newVariant */
      $newVariant = ProductVariant::query()->create($replicaData);

      $this->duplicateAttributeValues($variant, $newVariant);
      $this->duplicateVariantPrices($variant, $newVariant);
      $this->duplicateMedia($variant, $newVariant);

      CatalogCache::invalidate();

      return $newVariant;
    });
  }

  /**
   * Глубокое клонирование базового товара с модификациями.
   */
  public function replicateProduct(Product $product, bool $withVariants = true, array $overrides = []): Product
  {
    return DB::transaction(function () use ($product, $withVariants, $overrides) {
      $targetSlug = $overrides['slug'] ?? $this->generateUniqueProductSlug((string) $product->slug);

      $replicaData = array_merge($product->only([
        'catalog_type',
        'product_type_id',
        'category_id',
        'unit_id',
        'min_price',
        'is_active',
        'sort_order',
        'settings',
      ]), [
        'slug'          => $targetSlug,
        'code'          => null,
        'external_code' => null,
      ]);

      foreach (['name', 'short_description', 'description'] as $field) {
        if (isset($overrides[$field])) {
          $replicaData[$field] = $overrides[$field];
        } elseif (!empty($product->{$field})) {
          $replicaData[$field] = $product->getTranslations($field);
        }
      }

      foreach ($overrides as $key => $value) {
        if (!in_array($key, ['slug', 'name', 'short_description', 'description'], true)) {
          $replicaData[$key] = $value;
        }
      }

      /** @var Product $newProduct */
      $newProduct = Product::query()->create($replicaData);

      $this->duplicateAttributeValues($product, $newProduct);
      $this->duplicateMedia($product, $newProduct);

      if ($withVariants) {
        $variants = $product->variants()->orderBy('id')->get();
        foreach ($variants as $index => $variant) {
          $variantOverrides = ['product_id' => $newProduct->id];
          if ($index === 0) {
            $variantOverrides['is_default'] = true;
          }
          $this->replicateVariant($variant, $variantOverrides);
        }
        $newProduct->refreshMinPrice();
      }

      CatalogCache::invalidate();

      return $newProduct;
    });
  }

  /**
   * @since 2026-09-29: Делегирование генерации SKU в SkuGeneratorService
   */
  public function generateUniqueVariantSku(string $baseSku, ?Product $product = null): string
  {
    return app(SkuGeneratorService::class)->generate($product);
  }

  /**
   * Генерация уникального слага товара.
   */
  public function generateUniqueProductSlug(string $baseSlug): string
  {
    $candidate = "{$baseSlug}-copy";
    if (!Product::query()->where('slug', $candidate)->exists()) {
      return $candidate;
    }

    $index = 2;
    while (Product::query()->where('slug', "{$baseSlug}-copy-{$index}")->exists()) {
      $index++;
    }

    return "{$baseSlug}-copy-{$index}";
  }

  protected function duplicateAttributeValues(Model $source, Model $target): void
  {
    foreach ($source->attributeValues()->get() as $attrValue) {
      /** @var ProductAttributeValue $replica */
      $replica = $attrValue->replicate(['attributable_id', 'attributable_type']);
      $replica->attributable_id = $target->id;
      $replica->attributable_type = $target->getMorphClass();
      $replica->save();
    }
  }

  protected function duplicateVariantPrices(ProductVariant $source, ProductVariant $target): void
  {
    foreach ($source->prices()->get() as $priceRecord) {
      /** @var ProductVariantPrice $replica */
      $replica = $priceRecord->replicate(['product_variant_id']);
      $replica->product_variant_id = $target->id;
      $replica->save();
    }
  }

  protected function duplicateMedia(Model $source, Model $target): void
  {
    try {
      foreach ($source->media as $mediaItem) {
        $mediaItem->copy($target, $mediaItem->collection_name);
      }
    } catch (\Throwable $e) {
      Log::warning("ReplicationService: Не удалось скопировать медиафайл: " . $e->getMessage());
    }
  }
}