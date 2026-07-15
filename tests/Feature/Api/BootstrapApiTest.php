<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SettingKey as SK;

class BootstrapApiTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Создаем обязательный контекст канала продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора', 'en' => 'Calculator Widget'],
      'is_active' => true,
    ]);

    // Создаем базовую валюту
    Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'symbol' => '₽',
      'is_default' => true,
    ]);

    // Создаем дефолтный тип цен
    PriceType::factory()->create([
      'slug' => 'retail',
      'name' => ['ru' => 'Розничная цена', 'en' => 'Retail'],
      'description' => ['ru' => 'Основной прайс-лист', 'en' => 'Main price list'],
      'is_default' => true,
    ]);

    // Создаем Семейство товаров с активной схемой и настройками видимости в меню
    $family = ProductFamily::factory()->create([
      'code' => 'stone',
      'meta_schema' => [
        [
          SchemaKey::KEY => 'max_slabs',
          SchemaKey::TYPE => SchemaFieldType::NUMBER,
          SchemaKey::LABEL => [
            'ru' => 'Максимум плит в пачке',
            'en' => 'Max Slabs',
          ],
        ]
      ],
      'settings' => [
        'channels' => [
          'widget' => [
            SK::IS_PUBLIC => true,
            SK::IS_SETTINGS_PUBLIC => true,
            SK::SHOW_IN_MENU => true,
          ]
        ]
      ]
    ]);

    // Создаем Тип товара, привязанный к семейству
    ProductType::factory()->create([
      'family_id' => $family->id,
      'code' => 'acrylic_stone',
      'meta' => [
        'custom_key' => 'custom_value',
      ],
      'settings' => [
        'channels' => [
          'widget' => [
            SK::IS_PUBLIC => true,
            SK::IS_SETTINGS_PUBLIC => true,
          ]
        ]
      ]
    ]);

    // Создаем Умный Справочник без ценового поля (используем числовое поле 'material_density')
    $complexDictionary = ComplexDictionary::factory()->create([
      'code' => 'density_group',
      'meta_schema' => [
        [
          SchemaKey::KEY => 'material_density',
          SchemaKey::TYPE => SchemaFieldType::NUMBER,
          SchemaKey::IS_PUBLIC => true,
          SchemaKey::LABEL => [
            'ru' => 'Плотность',
            'en' => 'Density',
          ],
        ]
      ],
      'settings' => [
        'channels' => [
          'widget' => [
            SK::IS_PUBLIC => true,
            SK::IS_SETTINGS_PUBLIC => true,
          ]
        ]
      ]
    ]);

    // Создаем запись умного справочника (Плотность: 1.5)
    ComplexDictionaryRecord::factory()->create([
      'dictionary_id' => $complexDictionary->id,
      'slug' => 'm0',
      'meta' => [
        'material_density' => 1.5,
      ],
    ]);
  }

  /**
   * Сценарий: Проверка полной структуры инициализации канала.
   */
  public function test_bootstrap_endpoint_returns_success_and_correct_structure(): void
  {
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'ru',
    ])->getJson('/api/v1/bootstrap');

    $response->assertStatus(200);

    // Проверяем полную JSON-структуру ответа
    $response->assertJsonStructure([
      'status',
      'data' => [
        'base_currency' => [
          'code',
          'symbol',
        ],
        'price_types',
        'dictionaries' => [
          '*' => [
            'code',
            'name',
            'schema',
            'records' => [
              '*' => [
                'id',
                'slug',
                'name',
                'meta' => [
                  'material_density', // Проверяем возвращаемый ключ плотности
                ],
              ],
            ],
          ],
        ],
        'families' => [
          '*' => [
            'code',
            'name',
            'schema',
            'types' => [
              '*' => [
                'code',
                'name',
                'meta',
              ],
            ],
          ],
        ],
      ],
    ]);

    $response->assertJsonPath('status', 'success');
    $response->assertJsonPath('data.base_currency.code', 'RUB');

    // Извлекаем значение плотности из ответа
    $density = $response->json('data.dictionaries.0.records.0.meta.material_density');

    // Проверяем корректность возвращенных данных
    $this->assertIsNumeric($density);
    $this->assertEquals(1.5, $density);
  }

  /**
   * Сценарий: Блокировка запросов от некорректного или отсутствующего канала продаж.
   */
  public function test_bootstrap_endpoint_requires_valid_channel(): void
  {
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'unknown_channel',
    ])->getJson('/api/v1/bootstrap');

    $response->assertStatus(403);
    $response->assertJsonFragment([
      'error' => 'Invalid or inactive sales channel',
    ]);
  }

}
