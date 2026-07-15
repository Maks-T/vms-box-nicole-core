<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Jobs;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;
use Nicole\Box\Core\Jobs\RecalculateCatalogPricesJob;

class RecalculateCatalogPricesJobTest extends TestCase
{
  use LazilyRefreshDatabase;

  /**
   * Сценарий: Проверка работы джобы пересчета цен при изменении курса валюты закупки.
   */
  public function test_recalculate_catalog_prices_job_updates_product_min_prices(): void
  {
    // 1. Создаем валюты: базовую (RUB) и валюту закупки (USD, курс 100)
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    $usd = Currency::factory()->create([
      'code' => 'USD',
      'rate' => 100.0, // Изначальный курс 100 рублей за доллар
      'is_default' => false,
    ]);

    // 2. Создаем дефолтный тип цен
    $retailPriceType = PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);

    // 3. Создаем активный товар и его модификацию в валюте закупки USD с ценой 10 USD
    $product = Product::factory()->create(['min_price' => 0.0]);

    $variant = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'cost_price' => 10.0,
      'currency' => 'USD',
      'is_manual_pricing' => true,
      'is_active' => true,
    ]);

    ProductVariantPrice::factory()->create([
      'product_variant_id' => $variant->id,
      'price_type_id' => $retailPriceType->id,
      'markup_percent' => 0.0,
    ]);

    // Изначальный пересчет: 10 USD * 100 (курс) = 1000.00 рублей
    $product->refresh();
    $this->assertEquals(1000.0, $product->min_price);

    // 4. Имитируем изменение курса валюты: доллар вырос со 100 до 110 рублей
    $usd->updateQuietly(['rate' => 110.0]);

    // Очищаем старый инстанс PricingManager в сервис-контейнере,
    // чтобы при следующем вызове джоба получила свежие курсы из БД
    app()->forgetInstance(\Nicole\Box\Core\Services\PricingManager::class);

    // До запуска джобы минимальная цена товара в БД всё еще старая (1000 рублей)
    $this->assertEquals(1000.0, $product->fresh()->min_price);

    // 5. Запускаем джобу пересчета цен синхронно
    RecalculateCatalogPricesJob::dispatchSync('USD');

    // Проверяем, что джоба успешно пересчитала минимальную цену в БД с учетом нового курса (10 USD * 110 = 1100 рублей)
    $this->assertEquals(1100.0, $product->fresh()->min_price);
  }

}
