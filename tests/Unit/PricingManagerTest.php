<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Unit;

use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Services\PricingManager;

class PricingManagerTest extends TestCase
{
  use LazilyRefreshDatabase;



  protected PricingManager $pricingManager;

  protected function setUp(): void
  {
    parent::setUp();

    $this->pricingManager = app(PricingManager::class);
  }

  /**
   * Сценарий 1: Конвертация из иностранной валюты в базовую.
   */
  public function test_it_converts_foreign_currency_to_base_currency(): void
  {
    // Создаем базовую валюту (Рубли)
    Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    // Создаем иностранную валюту (Доллары, курс 95.5) [2]
    Currency::factory()->create([
      'code' => 'USD',
      'rate' => 95.5,
      'is_default' => false,
    ]);

    // Конвертируем 10 USD в RUB (10 * 95.5 = 955.0)
    $result = $this->pricingManager->convert(10.0, 'USD', 'RUB');

    $this->assertEquals(955.0, $result);
  }

  /**
   * Сценарий 2: Конвертация из базовой валюты в иностранную.
   */
  public function test_it_converts_base_currency_to_foreign_currency(): void
  {
    Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    Currency::factory()->create([
      'code' => 'USD',
      'rate' => 95.5,
      'is_default' => false,
    ]);

    // Конвертируем 955 RUB в USD (955 / 95.5 = 10.0)
    $result = $this->pricingManager->convert(955.0, 'RUB', 'USD');

    $this->assertEquals(10.0, $result);
  }

  /**
   * Сценарий 3: Сложная кросс-конвертация двух иностранных валют.
   */
  public function test_it_converts_between_two_foreign_currencies(): void
  {
    Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    Currency::factory()->create([
      'code' => 'USD',
      'rate' => 100.0, // Условный курс 100 рублей за доллар
      'is_default' => false,
    ]);

    Currency::factory()->create([
      'code' => 'EUR',
      'rate' => 110.0, // Условный курс 110 рублей за евро
      'is_default' => false,
    ]);

    // Конвертируем 110 USD в EUR:
    // 110 USD * 100 (курс USD) = 11000 рублей -> 11000 / 110 (курс EUR) = 100 EUR [2]
    $result = $this->pricingManager->convert(110.0, 'USD', 'EUR');

    $this->assertEquals(100.0, $result);
  }

  /**
   * Сценарий 4: Тестирование динамического расчета цены SKU на основе Умного Справочника.
   */
  /**
   * Тестирование динамического расчета цены SKU на основе выделенной Ценовой Группы.
   */
  public function test_it_calculates_price_from_complex_dictionary(): void
  {
    // Создаем валюты (Базовая RUB и валюта закупки USD)
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    $usd = Currency::factory()->create([
      'code' => 'USD',
      'rate' => 100.0, // Условный курс закупки: 100 рублей за доллар
      'is_default' => false,
    ]);

    // Создаем дефолтный тип цен
    $retailPriceType = \Nicole\Box\Core\Models\PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);

    // Создаем ценовую группу с базовой стоимостью 100 USD и наценкой 15%
    $priceGroup = \Nicole\Box\Core\Models\PriceGroup::create([
      'slug' => 'standard_stone',
      'name' => ['ru' => 'Стандартный камень'],
      'meta' => [
        'purchase_cost' => 100.0,
        'purchase_currency' => 'USD',
        'purchase_cost_markup' => 15.0, // Наценка 15%
      ],
      'is_active' => true,
    ]);

    // Создаем товар и привязываем модификацию (SKU) к нашей ценовой группе
    $product = Product::factory()->create();

    $variant = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'price_group_id' => $priceGroup->id,
      'is_manual_pricing' => false, // Используем автоматический групповой расчет
      'cost_price' => 0.0,
    ]);

    // Запускаем расчет цены модификации через PricingManager
    // Ожидаемый расчет:
    // 100 USD (закупка) * 100 (курс USD к RUB) = 10 000 рублей.
    // 10 000 рублей + 15% (наценка) = 11 500 рублей.
    $calculatedPrice = $this->pricingManager->getVariantPrice($variant);

    $this->assertEquals(11500.0, $calculatedPrice);
  }

}
