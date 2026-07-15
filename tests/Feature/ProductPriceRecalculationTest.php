<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;

class ProductPriceRecalculationTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected PriceType $retailPriceType;

  protected function setUp(): void
  {
    parent::setUp();

    // Создаем базовую валюту (Рубли)
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    // Создаем дефолтный тип цен
    $this->retailPriceType = PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);
  }

  /**
   * Сценарий 1: Автоматическое обновление min_price при создании первой цены.
   */
  public function test_product_min_price_updates_when_variant_price_is_created(): void
  {
    // Создаем товар без вариантов
    $product = Product::factory()->create(['min_price' => 0.0]);

    $this->assertEquals(0.0, $product->min_price);

    // Создаем модификацию товара с ценой закупки 5000 рублей в ручном режиме
    $variant = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'is_active' => true,
      'cost_price' => 5000.0,
      'is_manual_pricing' => true,
    ]);

    // Создаем цену для модификации (с наценкой 0%, итоговая цена = 5000.00)
    ProductVariantPrice::factory()->create([
      'product_variant_id' => $variant->id,
      'price_type_id' => $this->retailPriceType->id,
      'markup_percent' => 0.0,
    ]);

    // Проверяем, что товар автоматически пересчитал свою минимальную цену
    $product->refresh();
    $this->assertEquals(5000.0, $product->min_price);
  }

  /**
   * Сценарий 2: Выбор минимальной цены среди нескольких вариантов и автопересчет при отключении одного из них.
   */
  public function test_product_min_price_takes_the_lowest_active_variant_price(): void
  {
    $product = Product::factory()->create(['min_price' => 0.0]);

    // Создаем дорогой вариант (12 000 рублей)
    $variant1 = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'is_active' => true,
      'cost_price' => 12000.0,
      'is_manual_pricing' => true,
    ]);
    ProductVariantPrice::factory()->create([
      'product_variant_id' => $variant1->id,
      'price_type_id' => $this->retailPriceType->id,
      'markup_percent' => 0.0,
    ]);

    // Создаем дешевый вариант (8500 рублей)
    $variant2 = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'is_active' => true,
      'cost_price' => 8500.0,
      'is_manual_pricing' => true,
    ]);
    ProductVariantPrice::factory()->create([
      'product_variant_id' => $variant2->id,
      'price_type_id' => $this->retailPriceType->id,
      'markup_percent' => 0.0,
    ]);

    // Товар должен выбрать минимальную цену из двух - 8500
    $product->refresh();
    $this->assertEquals(8500.0, $product->min_price);

    // Отключаем дешевый вариант (делаем его неактивным)
    $variant2->update(['is_active' => false]);

    // Товар должен автоматически переключить минимальную цену на оставшийся активный вариант - 12000
    $product->refresh();
    $this->assertEquals(12000.0, $product->min_price);
  }

}
