<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\ListCurrencies;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\CreateCurrency;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\EditCurrency;
use Livewire\Livewire;

class CurrencyResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка валют в таблице.
   */
  public function test_can_render_list_currencies_page(): void
  {
    $this->actingAs($this->adminUser);

    $currency = Currency::factory()->create(['code' => 'USD']);

    Livewire::test(ListCurrencies::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$currency]);
  }

  /**
   * Сценарий: Создание новой валюты через форму.
   */
  public function test_can_create_currency_via_form(): void
  {
    $this->actingAs($this->adminUser);

    // Сначала создаем базовую валюту, чтобы EUR создался как дополнительная
    Currency::factory()->create(['code' => 'RUB', 'rate' => 1.0, 'is_default' => true]);

    // Записываем данные напрямую в свойства Livewire-компонента
    Livewire::test(CreateCurrency::class)
      ->set('data.name.ru', 'Евро')
      ->set('data.name.en', 'Euro')
      ->set('data.code', 'EUR')
      ->set('data.symbol', '€')
      ->set('data.rate', 105.0)
      ->set('data.is_active', true)
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем, что запись физически появилась в БД с правильным курсом
    $this->assertDatabaseHas('currencies', [
      'code' => 'EUR',
      'rate' => 105.0,
    ]);

  }

  /**
   * Сценарий: Проверка валидации полей (код валюты обязателен).
   */
  public function test_currency_code_is_required_to_create(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateCurrency::class)
      ->set('data.name.ru', 'Без кода')
      ->set('data.code', '')
      ->call('create')
      ->assertHasFormErrors(['code' => 'required']);
  }

  /**
   * Сценарий: Редактирование существующей валюты.
   */
  public function test_can_edit_currency_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Currency::factory()->create(['code' => 'RUB', 'is_default' => true]);

    $currency = Currency::factory()->create([
      'code' => 'USD',
      'rate' => 95.0,
      'is_default' => false,
    ]);

    Livewire::test(EditCurrency::class, [
      'record' => $currency->getKey(),
    ])
      ->set('data.rate', 98.5)
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertEquals(98.5, $currency->refresh()->rate);
  }

}