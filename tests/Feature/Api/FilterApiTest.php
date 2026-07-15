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

class FilterApiTest extends TestCase
{
  use LazilyRefreshDatabase;

  

  protected Attribute $colorAttribute;
  protected AttributeOption $optWhite;
  protected AttributeOption $optBlack;
  protected ProductType $stoneType;

  protected function setUp(): void
  {
    parent::setUp();

    // 1. Создаем канал продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => true,
    ]);

    $rub = Currency::factory()->create(['code' => 'RUB', 'rate' => 1.0, 'is_default' => true]);
    PriceType::factory()->create(['slug' => 'retail', 'is_default' => true, 'currency_id' => $rub->id]);

    $family = ProductFamily::factory()->create(['code' => 'stone']);

    $this->stoneType = ProductType::factory()->create([
      'family_id' => $family->id,
      'code' => 'acrylic_stone',
    ]);

    // Создаем EAV-атрибут "color" с обязательными настройками фильтрации в JSON
    $this->colorAttribute = Attribute::factory()->create([
      'code' => 'color',
      'type' => Attribute::TYPE_DICTIONARY,
      'settings' => [
        'channels' => [
          'widget' => [
            'is_public' => true,
            'is_filterable' => true, // Флаг: выводить этот атрибут в фильтрах
            'sort_order' => 10,
          ]
        ]
      ]
    ]);

    // Привязываем этот атрибут к типу продукта, иначе ядро не сопоставит его с семейством
    $this->stoneType->attributes()->sync([
      $this->colorAttribute->id => ['is_variant_only' => false, 'sort_order' => 10]
    ]);

    // Создаем варианты цветов в справочнике
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
   * Сценарий: Проверка автоматического отсечения неиспользуемых опций из фильтров.
   */
  public function test_filter_endpoint_returns_only_used_attribute_options(): void
  {
    // Создаем товар и привязываем к нему только Белый цвет
    $product = Product::factory()->create([
      'product_type_id' => $this->stoneType->id,
      'name' => ['ru' => 'Белый мрамор'],
    ]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);

    ProductAttributeValue::factory()->create([
      'attribute_id' => $this->colorAttribute->id,
      'attributable_id' => $product->id,
      'attributable_type' => $product->getMorphClass(),
      'value_option_id' => $this->optWhite->id, // Привязали белый
    ]);

    // Черный цвет ($this->optBlack) не привязан ни к одному товару

    // Делаем GET-запрос к API фильтров семейства stones
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'ru',
    ])->getJson('/api/v1/stones/filters');

    // Ожидаем успешный статус 200
    $response->assertStatus(200);

    // Точечные и надежные проверки путей JSON
    // Проверяем, что вернулся ровно 1 фильтруемый атрибут
    $response->assertJsonCount(1, 'data');

    // Проверяем его код
    $response->assertJsonPath('data.0.code', 'color');

    // Проверяем, что у него ровно 1 доступная опция (Черный отсечен)
    $response->assertJsonCount(1, 'data.0.options');

    // Проверяем значение оставшейся опции
    $response->assertJsonPath('data.0.options.0.slug', 'white');
    $response->assertJsonPath('data.0.options.0.value', 'Белый');

    // Дополнительно проверяем, что Черного цвета вообще нет в ответе
    $response->assertJsonMissing([
      'slug' => 'black',
      'value' => 'Черный',
    ]);
  }

}
