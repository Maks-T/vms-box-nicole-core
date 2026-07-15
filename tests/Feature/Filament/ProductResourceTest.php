<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Filament\Resources\Products\Pages\ListProducts;
use Nicole\Box\Core\Filament\Resources\Products\Pages\CreateProduct;
use Nicole\Box\Core\Filament\Resources\Products\Pages\EditProduct;
use Livewire\Livewire;

class ProductResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    // Инициализируем администратора
    $this->setUpFilamentAdmin();

    // Создаем базовые валюту и тип цен
    $rub = Currency::factory()->create(['code' => 'RUB', 'rate' => 1.0, 'is_default' => true]);
    PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);

    // Создаем каналы
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора', 'en' => 'Calculator Widget'],
      'is_active' => true,
    ]);

    // Создаем кастомную схему настроек товара, чтобы протестировать все типы полей в SalesChannelsTab
    \Nicole\Box\Core\Models\SettingSchema::updateOrCreate(['entity_type' => 'product'], [
      'meta_schema' => [
        [
          'key' => 'badge_text',
          'type' => 'text',
          'label' => [
            'ru' => 'Текст бейджа',
            'en' => 'Badge text',
          ],
          'width' => 1,
        ],
        [
          'key' => 'custom_rating',
          'type' => 'number',
          'label' => [
            'ru' => 'Пользовательский рейтинг',
            'en' => 'Custom rating',
          ],
          'width' => 1,
        ],
        [
          'key' => 'custom_select',
          'type' => 'select',
          'label' => [
            'ru' => 'Кастомный выбор',
            'en' => 'Custom select',
          ],
          'options' => [
            'opt_1' => [
              'ru' => 'Опция 1',
              'en' => 'Option 1',
            ],
          ],
          'width' => 1,
        ]
      ]
    ]);
  }

  /**
   * Сценарий: Проверка отображения товаров в таблице каталога.
   */
  public function test_can_render_list_products_page(): void
  {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create();

    Livewire::test(ListProducts::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$product]);
  }

  /**
   * Сценарий: Создание товара с заполнением EAV-характеристик, настроек каналов продаж и связанных компонентов.
   */
  public function test_can_create_product_with_dynamic_eav_attributes(): void
  {
    $this->actingAs($this->adminUser);

    $brandAttribute = Attribute::factory()->create([
      'code' => 'brand',
      'type' => Attribute::TYPE_STRING,
    ]);

    $productType = ProductType::factory()->create();
    $productType->attributes()->sync([
      $brandAttribute->id => [
        'is_required' => false,
        'is_variant_only' => false,
        'sort_order' => 10,
      ],
    ]);

    // Создаем товар, который привязываем в качестве связанного компонента
    $childProduct = Product::factory()->create();

    // Запускаем тест формы создания
    Livewire::test(CreateProduct::class)
      ->set('data.name.ru', 'Мойка кухонная')
      ->set('data.name.en', 'Kitchen Sink')
      ->set('data.slug', 'kitchen-sink')
      ->set('data.catalog_type', 'product')
      ->set('data.product_type_id', $productType->id)
      ->set("data.eav.{$brandAttribute->id}", 'Omoikiri')

      // Заполняем сложные динамические поля настроек канала продаж (SalesChannelsTab)
      ->set('data.settings.channels.widget.badge_text', 'Новинка')
      ->set('data.settings.channels.widget.custom_rating', 5)
      ->set('data.settings.channels.widget.custom_select', 'opt_1')

      // Заполняем репитер связанных элементов (LinkedItemsTab)
      ->set('data.linkedItems', [
        'linked_row_1' => [
          'temp_product_type' => $childProduct->product_type_id,
          'child_id' => $childProduct->id,
          'quantity_formula' => '2',
          'child_type' => get_class($childProduct),
        ]
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем, что товар создался в БД
    $product = Product::where('slug', 'kitchen-sink')->first();
    $this->assertNotNull($product);

    // Проверяем успешную запись связанных элементов в БД через репитер
    $this->assertDatabaseHas('binding_rules', [
      'parent_id' => $product->id,
      'parent_type' => $product->getMorphClass(),
      'child_id' => $childProduct->id,
      'quantity_formula' => '2',
    ]);

    // Проверяем, что все типы настроек канала продаж сохранились корректно
    $this->assertEquals('Новинка', $product->settings['channels']['widget']['badge_text']);
    $this->assertEquals(5, $product->settings['channels']['widget']['custom_rating']);
    $this->assertEquals('opt_1', $product->settings['channels']['widget']['custom_select']);
  }

  /**
   * Сценарий: Редактирование и обновление динамических EAV-характеристик товара через форму.
   */
  public function test_can_edit_product_and_update_dynamic_eav_attributes(): void
  {
    $this->actingAs($this->adminUser);

    $brandAttribute = Attribute::factory()->create([
      'code' => 'brand',
      'type' => Attribute::TYPE_STRING,
    ]);

    $productType = ProductType::factory()->create();
    $productType->attributes()->sync([
      $brandAttribute->id => [
        'is_required' => false,
        'is_variant_only' => false,
        'sort_order' => 10,
      ],
    ]);

    // Создаем товар и привязываем к нему изначальное EAV-значение "Franke"
    $product = Product::factory()->create([
      'product_type_id' => $productType->id,
    ]);

    ProductAttributeValue::factory()->create([
      'attribute_id' => $brandAttribute->id,
      'attributable_id' => $product->id,
      'attributable_type' => $product->getMorphClass(),
      'value_string' => 'Franke',
    ]);

    // Запускаем тест формы редактирования
    Livewire::test(EditProduct::class, [
      'record' => $product->getKey(),
    ])
      // Меняем бренд с "Franke" на "Blanco"
      ->set("data.eav.{$brandAttribute->id}", 'Blanco')
      ->call('save')
      ->assertHasNoFormErrors();

    // Проверяем, что в базе данных значение обновилось
    $this->assertDatabaseHas('product_attribute_values', [
      'attribute_id' => $brandAttribute->id,
      'attributable_id' => $product->id,
      'value_string' => 'Blanco',
    ]);

    $this->assertDatabaseMissing('product_attribute_values', [
      'attribute_id' => $brandAttribute->id,
      'attributable_id' => $product->id,
      'value_string' => 'Franke',
    ]);
  }

}
