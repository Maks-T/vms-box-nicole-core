<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Warehouse;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\CreateProductVariant;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\EditProductVariant;
use Livewire\Livewire;

class ProductVariantResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected PriceType $retailPriceType;
  protected Warehouse $warehouse;
  protected Product $product;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin(); // Инициализируем администратора

    // Создаем базовую валюту
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    // Создаем дефолтный тип цен
    /** @var PriceType $priceType */
    $priceType = PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);
    $this->retailPriceType = $priceType;

    // Создаем тестовый склад и подсказываем его тип для IDE
    /** @var Warehouse $warehouse */
    $warehouse = Warehouse::factory()->create([
      'slug' => 'main',
    ]);
    $this->warehouse = $warehouse;

    // Создаем родительский товар и подсказываем его тип для IDE
    /** @var Product $product */
    $product = Product::factory()->create();
    $this->product = $product;
  }

  /**
   * Сценарий: Проверка отображения списка модификаций в таблице.
   */
  public function test_can_render_list_product_variants_page(): void
  {
    $this->actingAs($this->adminUser);

    $variant = ProductVariant::factory()->create([
      'product_id' => $this->product->id,
      'sku' => 'stone_variant_sku',
    ]);

    Livewire::test(ListProductVariants::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$variant]);
  }

  /**
   * Сценарий: Создание модификации товара с заполнением цен и остатков по складам через репитеры.
   */
  public function test_can_create_product_variant_with_prices_and_stocks_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateProductVariant::class)
      ->set('data.product_id', $this->product->id)
      ->set('data.sku', 'custom_granite_sku')
      ->set('data.cost_price', 3000.0)
      ->set('data.currency', 'RUB')
      ->set('data.is_active', true)
      ->set('data.is_manual_pricing', true) // ВКЛЮЧАЕМ РУЧНОЙ РЕЖИМ
      // Заполняем репитер цен
      ->set('data.prices', [
        'price_row_1' => [
          'price_type_id' => $this->retailPriceType->id,
          'markup_percent' => 15.0, // Наценка 15% (итоговая цена будет 3450.00)
        ]
      ])
      // Заполняем репитер остатков
      ->set('data.stocks', [
        'stock_row_1' => [
          'warehouse_id' => $this->warehouse->id,
          'quantity' => 25.0,
        ]
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    $variant = ProductVariant::where('sku', 'custom_granite_sku')->first();
    $this->assertNotNull($variant);

    // Проверяем, что наценка успешно записалась в БД
    $this->assertDatabaseHas('product_variant_prices', [
      'product_variant_id' => $variant->id,
      'price_type_id' => $this->retailPriceType->id,
      'markup_percent' => 15.0000000000,
    ]);

    $this->assertDatabaseHas('stocks', [
      'product_variant_id' => $variant->id,
      'warehouse_id' => $this->warehouse->id,
      'quantity' => 25.0,
    ]);
  }

  /**
   * Сценарий: Изменение активности существующей модификации через форму редактирования.
   */
  public function test_can_edit_product_variant_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $variant = ProductVariant::factory()->create([
      'product_id' => $this->product->id,
      'sku' => 'editable_sku',
      'is_active' => true,
      'is_manual_pricing' => true,
    ]);

    // Создаем цену для модификации, чтобы репитер цен (матрица цен продаж) не был пустым при ручном режиме
    \Nicole\Box\Core\Models\ProductVariantPrice::create([
      'product_variant_id' => $variant->id,
      'price_type_id' => $this->retailPriceType->id,
      'markup_percent' => 15.00,
    ]);

    Livewire::test(EditProductVariant::class, [
      'record' => $variant->getKey(),
    ])
      ->set('data.is_active', false) // Деактивируем модификацию
      ->call('save')
      ->assertHasNoFormErrors();

    // Сверяем новое значение активности в базе данных
    $this->assertFalse($variant->refresh()->is_active);
  }

}
