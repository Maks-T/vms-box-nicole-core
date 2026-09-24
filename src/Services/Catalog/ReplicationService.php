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
use Spatie\MediaLibrary\HasMedia;

/**
 * Сервис глубокого клонирования товаров и торговых модификаций (SKU).
 *
 * @since 2026-09-24
 */
class ReplicationService
{
  /**
   * Глубокое клонирование модификации товара (SKU) со всеми связями.
   *
   * @param ProductVariant $variant Исходная модификация
   * @param array<string, mixed> $overrides Переопределяемые атрибуты (sku, name, cost_price и т.д.)
   * @return ProductVariant Созданная копия модификации
   */
  public function replicateVariant(ProductVariant $variant, array $overrides = []): ProductVariant
  {
    return DB::transaction(function () use ($variant, $overrides) {
      // Формирование базовых атрибутов копии
      $targetSku = $overrides['sku'] ?? $this->generateUniqueVariantSku((string)$variant->sku);

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
        'sku' => $targetSku,
        'external_code' => null, // Обнуляем внешний код интеграции
        'is_default' => false, // Копия не может быть дефолтной
        'stock' => 0.0, // Складской остаток новой позиции начинается с 0
      ]);

      // Клонирование названия (с учетом переводов Spatie Translatable)
      if (isset($overrides['name'])) {
        $replicaData['name'] = $overrides['name'];
      } elseif (!empty($variant->name)) {
        $replicaData['name'] = $variant->getTranslations('name');
      }

      // Применение остальных переопределений
      foreach ($overrides as $key => $value) {
        if (!in_array($key, ['sku', 'name'], true)) {
          $replicaData[$key] = $value;
        }
      }

      // Создание реплики в БД
      /** @var ProductVariant $newVariant */
      $newVariant = ProductVariant::query()->create($replicaData);

      // Дублирование EAV-характеристик (цвет, сплав, размеры и др.)
      $this->duplicateAttributeValues($variant, $newVariant);

      // Дублирование матрицы цен (наценки и себестоимости прайс-листов)
      $this->duplicateVariantPrices($variant, $newVariant);

      // Дублирование медиафайлов
      $this->duplicateMedia($variant, $newVariant);

      // Инвалидация кэша каталога
      CatalogCache::invalidate();

      return $newVariant;
    });
  }

    /**
     * Глубокое клонирование базового товара со всеми характеристиками, медиа и вариантами (SKU).
     *
     * @param Product $product Исходный товар
     * @param bool $withVariants Клонировать ли вложенные модификации товара (SKU)
     * @param array<string, mixed> $overrides Переопределяемые атрибуты товара
     * @return Product Созданная копия товара
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
                'code'          => null, // Сбрасываем для автоматической перегенерации
                'external_code' => null, // Обнуляем внешний код интеграции
            ]);

            // Клонирование транслируемых полей
            foreach (['name', 'short_description', 'description'] as $translatableField) {
                if (isset($overrides[$translatableField])) {
                    $replicaData[$translatableField] = $overrides[$translatableField];
                } elseif (!empty($product->{$translatableField})) {
                    $replicaData[$translatableField] = $product->getTranslations($translatableField);
                }
            }

            // Применение дополнительных переопределений
            foreach ($overrides as $key => $value) {
                if (!in_array($key, ['slug', 'name', 'short_description', 'description'], true)) {
                    $replicaData[$key] = $value;
                }
            }

            /** @var Product $newProduct */
            $newProduct = Product::query()->create($replicaData);

            // Дублирование EAV-характеристик уровня товара
            $this->duplicateAttributeValues($product, $newProduct);

            // Дублирование медиафайлов товара
            $this->duplicateMedia($product, $newProduct);

            // Каскадное клонирование всех дочерних SKU
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
   * Генерация уникального артикула SKU с защитой от коллизий.
   */
  public function generateUniqueVariantSku(string $baseSku): string
  {
    $candidate = "{$baseSku}-COPY";
    if (!ProductVariant::query()->where('sku', $candidate)->exists()) {
      return $candidate;
    }

    $index = 2;
    while (ProductVariant::query()->where('sku', "{$baseSku}-COPY-{$index}")->exists()) {
      $index++;
    }

    return "{$baseSku}-COPY-{$index}";
  }

    /**
     * Генерация уникального слага товара с защитой от коллизий.
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

  /**
   * Копирование полиморфных EAV-характеристик.
   */
    protected function duplicateAttributeValues(Model $source, Model $target): void
  {
    $sourceValues = $source->attributeValues()->get();

    foreach ($sourceValues as $attrValue) {
      ProductAttributeValue::query()->create([
        'attribute_id' => $attrValue->attribute_id,
        'attributable_type' => $target->getMorphClass(),
        'attributable_id' => $target->id,
        'value_string' => $attrValue->value_string,
        'value_numeric' => $attrValue->value_numeric,
        'value_boolean' => $attrValue->value_boolean,
        'value_option_id' => $attrValue->value_option_id,
        'value_complex_id' => $attrValue->value_complex_id,
        'value_entity_id' => $attrValue->value_entity_id,
      ]);
    }
  }

  /**
   * Копирование матрицы прайс-листов и наценок.
   */
  protected function duplicateVariantPrices(ProductVariant $source, ProductVariant $target): void
  {
    $sourcePrices = $source->prices()->get();

    foreach ($sourcePrices as $priceRecord) {
      ProductVariantPrice::query()->create([
        'product_variant_id' => $target->id,
        'price_type_id' => $priceRecord->price_type_id,
        'markup_percent' => $priceRecord->markup_percent,
        'price' => $priceRecord->price,
      ]);
    }
  }

  /**
   * Копирование прикреплённых медиафайлов коллекции Spatie MediaLibrary.
   */
    protected function duplicateMedia(Model $source, Model $target): void
  {
    try {
      foreach ($source->media as $mediaItem) {
        $mediaItem->copy($target, $mediaItem->collection_name);
      }
    } catch (\Throwable $e) {
      Log::warning("ReplicationService: Не удалось скопировать медиафайл для SKU {$target->sku}: " . $e->getMessage());
    }
  }
}