<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;

class ProductApiTest extends TestCase
{
  use LazilyRefreshDatabase; 

  protected Attribute $colorAttribute;
  protected AttributeOption $optWhite;
  protected AttributeOption $optBlack;
  protected ProductType $stoneType;

  protected function setUp(): void
  {
    parent::setUp();

    // Создаем обязательный контекст
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => true,
    ]);

    $rub = Currency::factory()->create(['code' => 'RUB', 'rate' => 1.0, 'is_default' => true]);
    PriceType::factory()->create(['slug' => 'retail', 'is_default' => true, 'currency_id' => $rub->id]);

    // Создаем Семейство товаров "stone" (камень)
    $family = ProductFamily::factory()->create(['code' => 'stone']);

    // Создаем Тип товара, привязанный к семейству
    $this->stoneType = ProductType::factory()->create([
      'family_id' => $family->id,
      'code' => 'acrylic_stone',
    ]);

    // Создаем EAV-атрибут "color"
    $this->colorAttribute = Attribute::factory()->create([
      'code' => 'color',
      'type' => Attribute::TYPE_DICTIONARY,
    ]);

    // Создаем опции цветов в справочнике
    $this->optWhite = AttributeOption::factory()->create([
      'attribute_id' => $this->colorAttribute->id,
      'slug' => 'white',
      'value' => ['ru' => 'Белый'],
    ]);

    $this->optBlack = AttributeOption::factory()->create([
      'attribute_id' => $this->colorAttribute->id,
      'slug' => 'black',
      'value' => ['ru' => 'Черный'],
    ]);
  }

  /**
   * Сценарий: Проверка фильтрации товаров по справочному EAV-атрибуту (цветам).
   */
  public function test_catalog_endpoint_filters_products_by_eav_correctly(): void
  {
    // Создаем БЕЛЫЙ камень
    $whiteProduct = Product::factory()->create([
      'product_type_id' => $this->stoneType->id,
      'name' => ['ru' => 'Белый гранит', 'en' => 'White granite'],
    ]);

    ProductVariant::factory()->create(['product_id' => $whiteProduct->id, 'is_active' => true]);

    ProductAttributeValue::factory()->create([
      'attribute_id' => $this->colorAttribute->id,
      'attributable_id' => $whiteProduct->id,
      'attributable_type' => $whiteProduct->getMorphClass(),
      'value_option_id' => $this->optWhite->id, // Цвет: Белый
    ]);

    // Создаем ЧЕРНЫЙ камень
    $blackProduct = Product::factory()->create([
      'product_type_id' => $this->stoneType->id,
      'name' => ['ru' => 'Черный сланец', 'en' => 'Black slate'],
    ]);
    ProductVariant::factory()->create(['product_id' => $blackProduct->id, 'is_active' => true]);

    ProductAttributeValue::factory()->create([
      'attribute_id' => $this->colorAttribute->id,
      'attributable_id' => $blackProduct->id,
      'attributable_type' => $blackProduct->getMorphClass(),
      'value_option_id' => $this->optBlack->id, // Цвет: Черный
    ]);

    // Выполняем запрос к каталогу семейства stone с фильтром по БЕЛОМУ цвету
    // url: /api/v1/stones/products?attr[color]=white
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'ru',
    ])->getJson('/api/v1/stones/products?attr[color]=white');

    // Ожидаем успешный статус
    $response->assertStatus(200);

    // В ответе должен быть только белый товар, черный должен отсечься фильтрацией
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment([
      'id' => $whiteProduct->id,
      'name' => 'Белый гранит',
    ]);

    $response->assertJsonMissing([
      'id' => $blackProduct->id,
      'name' => 'Черный сланец',
    ]);
  }

}
