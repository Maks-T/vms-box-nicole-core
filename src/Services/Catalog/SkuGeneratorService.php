<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Services\Catalog;

use Illuminate\Support\Str;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

/**
 * Сервис автоматической генерации уникальных артикулов модификаций (SKU).
 * Формирует лаконичные складские коды по шаблону: {product.code||slug||uniq4}-{uniq6}
 *
 * @since 2026-09-29
 */
class SkuGeneratorService
{
  /**
   * Сгенерировать гарантированно уникальный артикул для модификации товара.
   *
   * @param Product|int|null $product Родительский товар или его ID
   * @param string|null $prefix Кастомный префикс (если передан явно)
   * @return string
   */
  public function generate(Product|int|null $product = null, ?string $prefix = null): string
  {
    $basePrefix = $prefix ? $this->sanitizeSegment($prefix) : $this->resolveProductPrefix($product);

    do {
      $randomSuffix = Str::upper(Str::random(6));
      $candidateSku = "{$basePrefix}-{$randomSuffix}";
    } while (ProductVariant::query()->where('sku', $candidateSku)->exists());

    return $candidateSku;
  }

  /**
   * Разрешение префикса товара: code -> slug -> random(4).
   */
  protected function resolveProductPrefix(Product|int|null $product): string
  {
    if (is_int($product)) {
      $product = Product::query()->find($product);
    }

    if ($product instanceof Product) {
      if (!empty($product->code)) {
        return $this->sanitizeSegment((string) $product->code, 16);
      }

      if (!empty($product->slug)) {
        return $this->sanitizeSegment((string) $product->slug, 16);
      }
    }

    return Str::upper(Str::random(4));
  }

  /**
   * Очистка и нормализация сегмента артикула (буквы, цифры, дефис).
   */
  protected function sanitizeSegment(string $value, int $maxLength = 20): string
  {
    $cleaned = (string) preg_replace('/[^a-zA-Z0-9_-]/', '', Str::upper($value));

    if (empty($cleaned)) {
      return Str::upper(Str::random(4));
    }

    return Str::limit($cleaned, $maxLength, '');
  }
}