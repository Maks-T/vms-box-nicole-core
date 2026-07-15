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
use Nicole\Box\Core\Models\OrderStatus;
use Nicole\Box\Core\Models\Order;

class OrderApiTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected ProductVariant $variant;

  protected function setUp(): void
  {
    parent::setUp();

    // 1. Создаем обязательный канал продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => true,
    ]);

    // 2. Создаем базовую валюту
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    // 3. Создаем базовый тип цен
    PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);

    // 4. Создаем статус по умолчанию для новых заказов
    OrderStatus::create([
      'slug' => 'draft',
      'name' => ['ru' => 'Черновик'],
      'is_default' => true,
      'is_active' => true,
    ]);

    // 5. Создаем тестовую модификацию товара для симуляции сметных товаров
    $product = Product::factory()->create();
    $this->variant = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'sku' => 'test-order-product-sku',
      'is_active' => true,
    ]);
  }

  /**
   * Сценарий: Проверка сквозного сохранения и получения заказа через API.
   */
  public function test_can_save_and_retrieve_order_via_api(): void
  {
    // Подготавливаем валидный payload (SaveData) от калькулятора
    $payload = [
      'calc_state' => ['some_key' => 'some_value'],
      'currency' => 'RUB',
      'grand_total' => 150000.0,
      'locale' => 'ru',
      'customer' => [
        'name' => 'Иванов Иван Иванович',
        'phone' => '+7 (999) 888-77-66',
        'email' => 'ivanov@example.com',
        'city' => 'Москва',
        'address' => 'ул. Ленина, д. 10, кв. 5',
      ],
      'results' => [
        [
          'id' => 'section_kitchen_top',
          'type' => 'kitchen',
          'title' => 'Кухонная столешница Г-образная',
          'price' => [
            'currency' => 'RUB',
            'total' => 150000.0,
            'grand_total' => 150000.0,
            'VAT' => 0.0,
            'VAT_percent' => 0.0,
            'discount' => 0.0,
            'discount_percent' => 0.0,
          ],
          'description' => [
            ['name' => 'Наименование камня', 'description' => 'Grandex M-701'],
            ['name' => 'Толщина', 'description' => '40 мм'],
          ],
          'estimate' => [
            [
              'value' => ['Столешница плита', '1', 'шт.', '100000', '100000'],
              'children' => []
            ],
            [
              'value' => ['Доставка и монтаж', '1', 'усл.', '50000', '50000'],
              'children' => []
            ]
          ],
          'meta' => [
            'properties' => [
              'form' => 'l-shape',
            ],
            'items' => [
              'slabs' => [
                [
                  'variant_id' => $this->variant->id,
                  'quantity' => 1.5,
                ]
              ]
            ]
          ]
        ]
      ]
    ];

    // 1. Отправляем POST-запрос на сохранение заказа
    $saveResponse = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'ru',
    ])->postJson('/api/v1/order/save', $payload);

    $saveResponse->assertStatus(201);
    $saveResponse->assertJsonPath('status', 'success');

    $orderCode = $saveResponse->json('data.code');
    $this->assertNotNull($orderCode);

    // 2. Отправляем GET-запрос на получение этого заказа по коду
    $getResponse = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'ru',
    ])->getJson("/api/v1/orders/{$orderCode}");

    $getResponse->assertStatus(200);

    $order = Order::with(['customer', 'status', 'sections.products', 'products'])->where('code', $orderCode)->firstOrFail();

    // Проверяем Order.php
    $this->assertEquals($order->code, $order->kp_number);
    $this->assertNotNull($order->status);
    $this->assertNull($order->manager); // Менеджер не задан в тесте, возвращает null
    $this->assertCount(1, $order->products);

    // Проверяем Customer.php
    $customer = $order->customer;
    $this->assertNotNull($customer);
    $this->assertEquals('Иванов Иван Иванович', $customer->full_name);
    $this->assertCount(1, $customer->orders);

    // Проверяем OrderSection.php
    $section = $order->sections->first();
    $this->assertNotNull($section);
    $this->assertEquals($order->id, $section->order->id);
    $this->assertCount(1, $section->products);

    // Проверяем OrderProduct.php
    $orderProduct = $order->products->first();
    $this->assertNotNull($orderProduct);
    $this->assertEquals($order->id, $orderProduct->order->id);
    $this->assertEquals($section->id, $orderProduct->section->id);
    $this->assertEquals($this->variant->id, $orderProduct->variant->id);
  }
}
