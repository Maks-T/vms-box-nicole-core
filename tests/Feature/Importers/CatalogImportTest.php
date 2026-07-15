<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Importers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\SettingSchema;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;
use Nicole\Box\Core\Models\Stock;

class CatalogImportTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected string $settingsPath;
  protected string $dataPath;
  protected string $servicesPath;

  protected function setUp(): void
  {
    parent::setUp();

    $this->settingsPath = storage_path('testing_import_settings.json');
    $this->dataPath = storage_path('testing_import_data.json');
    $this->servicesPath = storage_path('testing_import_services.json');

    // Запускаем сидер, необходимый для работы статусов и единиц измерения
    $this->seed(\Nicole\Box\Core\Database\Seeders\CommerceInfrastructureSeeder::class);

    // Наполняем временный файл настроек (import_settings.json)
    File::put($this->settingsPath, json_encode([
      'channels' => [
        'widget' => ['is_public_default' => true],
      ],
      'setting_schemas' => [
        'attribute' => [
          [
            'key' => 'is_public',
            'type' => 'boolean',
            'label' => ['ru' => 'Опубликовано', 'en' => 'Published'],
            'default' => true,
          ]
        ]
      ]
    ]));

    // Наполняем временный файл данных (import_data.json) с поддержкой валют и типов цен
    File::put($this->dataPath, json_encode([
      'currencies' => [
        [
          'code' => 'RUB',
          'symbol' => '₽',
          'symbol_native' => 'руб.',
          'name' => ['ru' => 'Рубль', 'en' => 'Ruble'],
          'rate' => 1.0,
          'is_default' => true,
          'is_active' => true,
        ]
      ],
      'price_types' => [
        [
          'slug' => 'retail',
          'name' => ['ru' => 'Розничная', 'en' => 'Retail'],
          'is_default' => true,
          'currency_code' => 'RUB',
        ]
      ],
      'families' => [
        [
          'external_code' => 'fam_stone',
          'code' => 'stone',
          'slug' => 'stone',
          'name' => ['ru' => 'Камень', 'en' => 'Stone'],
        ]
      ],
      'types' => [
        [
          'external_code' => 'type_acrylic_stone',
          'family_external_code' => 'fam_stone',
          'code' => 'acrylic_stone',
          'slug' => 'acrylic-stone',
          'name' => ['ru' => 'Акриловый камень', 'en' => 'Acrylic stone'],
        ]
      ],
      'categories' => [
        [
          'external_code' => 'cat_test_stone',
          'slug' => 'stone-slabs',
          'name' => ['ru' => 'Плиты камня', 'en' => 'Stone slabs'],
        ]
      ],
      'attributes' => [
        [
          'external_code' => 'attr_test_brand',
          'code' => 'brand',
          'type' => 'string',
          'name' => ['ru' => 'Бренд', 'en' => 'Brand'],
        ]
      ],
      'products' => [
        [
          'id' => 'prod_test_item',
          'external_code' => 'prod_test_item',
          'product_type_external_code' => 'type_acrylic_stone',
          'category_external_code' => 'cat_test_stone',
          'unit_code' => 'pcs',
          'slug' => 'test-stone-product',
          'name' => ['ru' => 'Камень А001', 'en' => 'Stone A001'],
          'eav' => [
            'brand' => 'Grandex',
          ],
          'variants' => [
            [
              'external_code' => 'sku_test_item',
              'sku' => 'test-stone-sku',
              'cost_price' => 3000.0,
              'price' => 5000.0,
              'stock' => 15.0,
              'is_default' => true,
              'eav' => [],
            ]
          ]
        ]
      ]
    ]));

    // Наполняем временный файл услуг (import_services.json)
    File::put($this->servicesPath, json_encode([
      'attributes' => [],
      'categories' => [
        'cutouts' => [
          'name' => ['ru' => 'Вырезы', 'en' => 'Cutouts'],
        ]
      ],
      'services' => [
        [
          'slug' => 'cutout_top',
          'category' => 'cutouts',
          'unit' => 'pcs',
          'name' => [
            'ru' => 'Вырез под мойку',
            'en' => 'Sink cutout',
          ],
          'prices' => [
            'acrylic_stone' => 1650.0,
          ]
        ]
      ]
    ]));
  }

  protected function tearDown(): void
  {
    // Удаляем временные файлы после прогона тестов
    File::delete($this->settingsPath);
    File::delete($this->dataPath);
    File::delete($this->servicesPath);

    parent::tearDown();
  }

  /**
   * Сценарий: Сквозной запуск импорта из временных файлов и проверка результатов в БД.
   */
  public function test_modular_catalog_import_runs_successfully(): void
  {
    // Запускаем команду импорта, передавая пути относительно корня проекта
    $exitCode = Artisan::call('vms:import', [
      '--settings' => 'storage/testing_import_settings.json',
      '--data' => 'storage/testing_import_data.json',
      '--services' => 'storage/testing_import_services.json',
    ]);

    // Проверяем, что консольная команда завершилась без ошибок
    $this->assertEquals(0, $exitCode);

    // Проверяем импорт настроек канала из SettingsImporter
    $channel = Channel::where('code', 'widget')->first();
    $this->assertNotNull($channel);

    $settingSchema = SettingSchema::where('entity_type', 'attribute')->first();
    $this->assertNotNull($settingSchema);
    $this->assertEquals('is_public', $settingSchema->meta_schema[0]['key']);

    // Проверяем импорт иерархии категорий из CategoryImporter
    $category = Category::where('slug', 'stone-slabs')->first();
    $this->assertNotNull($category);

    // Проверяем импорт товаров из ProductImporter
    $product = Product::where('slug', 'test-stone-product')->first();
    $this->assertNotNull($product);
    $this->assertEquals('Камень А001', $product->getTranslation('name', 'ru'));

    // Проверяем импорт модификации (SKU)
    $variant = ProductVariant::where('sku', 'test-stone-sku')->first();
    $this->assertNotNull($variant);
    $this->assertEquals(3000.0, $variant->cost_price);

    // Проверяем создание цены модификации
    $this->assertDatabaseHas('product_variant_prices', [
      'product_variant_id' => $variant->id,
      'markup_percent' => 66.6666666667,
    ]);

    // Проверяем создание остатков на складе
    $this->assertDatabaseHas('stocks', [
      'product_variant_id' => $variant->id,
      'quantity' => 15.0,
    ]);

    // Проверяем привязку динамической EAV-характеристики
    $this->assertDatabaseHas('product_attribute_values', [
      'attributable_id' => $product->id,
      'attributable_type' => $product->getMorphClass(),
      'value_string' => 'Grandex',
    ]);

    // Проверяем импорт услуг обработки из ServiceImporter
    $service = Product::where('slug', 'cutout_top')->where('catalog_type', 'service')->first();
    $this->assertNotNull($service);
    $this->assertEquals('Вырез под мойку', $service->getTranslation('name', 'ru'));
  }

}
