<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Middleware;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Illuminate\Support\Facades\App;

class EnforceChannelContextTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Создаем базовые валюты и цены для успешного прохождения bootstrap роута
    Currency::factory()->create(['code' => 'RUB', 'is_default' => true]);
    PriceType::factory()->create(['slug' => 'retail', 'is_default' => true]);
  }

  /**
   * Сценарий: Если заголовок X-Sales-Channel пустой, возвращается ошибка 400.
   */
  public function test_it_returns_400_if_sales_channel_header_is_empty(): void
  {
    $response = $this->withHeaders([
      'X-Sales-Channel' => '', // Передаем пустое значение
    ])->getJson('/api/v1/bootstrap');

    $response->assertStatus(400);
    $response->assertJsonFragment([
      'error' => 'Header X-Sales-Channel is required',
    ]);
  }

  /**
   * Сценарий: Если канал не существует в БД, возвращается ошибка 403.
   */
  public function test_it_returns_403_if_sales_channel_does_not_exist(): void
  {
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'non_existent_channel',
    ])->getJson('/api/v1/bootstrap');

    $response->assertStatus(403);
    $response->assertJsonFragment([
      'error' => 'Invalid or inactive sales channel',
    ]);
  }

  /**
   * Сценарий: Если канал существует, но неактивен в БД, возвращается ошибка 403.
   */
  public function test_it_returns_403_if_sales_channel_is_inactive(): void
  {
    // Создаем неактивный канал продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => false, // Неактивен
    ]);

    $response = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
    ])->getJson('/api/v1/bootstrap');

    $response->assertStatus(403);
    $response->assertJsonFragment([
      'error' => 'Invalid or inactive sales channel',
    ]);
  }

  /**
   * Сценарий: Если канал активен, проверяется корректность установки языка и конфигурационного контекста.
   */
  public function test_it_sets_locale_and_config_for_valid_channel(): void
  {
    // Создаем активный канал продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => true,
    ]);

    // Выполняем корректный запрос с передачей английской локали
    $response = $this->withHeaders([
      'X-Sales-Channel' => 'widget',
      'Accept-Language' => 'en-US,en;q=0.9',
    ])->getJson('/api/v1/bootstrap');

    $response->assertSuccessful();

    // Проверяем, что локаль приложения успешно переключилась на en
    $this->assertEquals('en', App::getLocale());

    // Проверяем, что канал успешно записался в конфигурацию app.channel для ресурсов
    $this->assertEquals('widget', config('app.channel'));
  }

}
