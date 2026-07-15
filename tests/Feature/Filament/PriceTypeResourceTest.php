<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\ListPriceTypes;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\CreatePriceType;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\EditPriceType;
use Livewire\Livewire;

class PriceTypeResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected Currency $currency;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();

    // Создаем базовую валюту для связи с типом цен
    /** @var Currency $currency */
    $currency = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);
    $this->currency = $currency;
  }

  /**
   * Сценарий: Проверка отображения списка типов цен в таблице Filament.
   */
  public function test_can_render_list_price_types_page(): void
  {
    $this->actingAs($this->adminUser);

    $priceType = PriceType::factory()->create([
      'slug' => 'wholesale',
      'currency_id' => $this->currency->id,
    ]);

    Livewire::test(ListPriceTypes::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$priceType]);
  }

  /**
   * Сценарий: Создание нового типа цен через форму.
   */
  public function test_can_create_price_type_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreatePriceType::class)
      ->set('data.name.ru', 'Дилерская цена')
      ->set('data.name.en', 'Dealer Price')
      ->set('data.slug', 'dealer_price')
      ->set('data.currency_id', $this->currency->id)
      ->set('data.is_default', false)
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем появление записи в СУБД
    $this->assertDatabaseHas('price_types', [
      'slug' => 'dealer_price',
      'currency_id' => $this->currency->id,
    ]);
  }

  /**
   * Сценарий: Редактирование описания типа цен.
   */
  public function test_can_edit_price_type_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $priceType = PriceType::factory()->create([
      'slug' => 'dealer',
      'currency_id' => $this->currency->id,
      'description' => [
        'ru' => 'Старое описание',
      ],
    ]);

    Livewire::test(EditPriceType::class, [
      'record' => $priceType->getKey(),
    ])
      ->set('data.description.ru', 'Новое описание типа цен')
      ->call('save')
      ->assertHasNoFormErrors();

    // Проверяем обновление перевода в БД
    $this->assertEquals('Новое описание типа цен', $priceType->refresh()->getTranslation('description', 'ru'));
  }

}