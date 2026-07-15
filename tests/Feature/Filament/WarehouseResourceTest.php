<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Warehouse;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\ListWarehouses;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\EditWarehouse;
use Livewire\Livewire;

class WarehouseResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка складов в таблице Filament.
   */
  public function test_can_render_list_warehouses_page(): void
  {
    $this->actingAs($this->adminUser);

    $warehouse = Warehouse::factory()->create(['slug' => 'moscow_warehouse']);

    Livewire::test(ListWarehouses::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$warehouse]);
  }

  /**
   * Сценарий: Создание нового склада с заполнением расписания работы.
   */
  public function test_can_create_warehouse_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateWarehouse::class)
      ->set('data.name.ru', 'Центральный Склад')
      ->set('data.name.en', 'Central Warehouse')
      ->set('data.slug', 'central-warehouse')
      ->set('data.phone', '+79998887766')
      ->set('data.email', 'central@vms-box.local')
      ->set('data.address', 'г. Москва, ул. Складская, д. 5')
      ->set('data.latitude', 55.7558)
      ->set('data.longitude', 37.6173)
      // Наполняем KeyValue компонент расписания работы склада
      ->set('data.schedule', [
        'Пн-Пт' => '09:00 - 18:00',
        'Сб' => '10:00 - 15:00',
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем физическое появление склада в БД
    $this->assertDatabaseHas('warehouses', [
      'slug' => 'central-warehouse',
      'email' => 'central@vms-box.local',
      'latitude' => 55.7558,
    ]);
  }

  /**
   * Сценарий: Редактирование адреса существующего склада.
   */
  public function test_can_edit_warehouse_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $warehouse = Warehouse::factory()->create([
      'slug' => 'main_wh',
      'address' => 'Старый адрес',
    ]);

    Livewire::test(EditWarehouse::class, [
      'record' => $warehouse->getKey(),
    ])
      ->set('data.address', 'Новый адрес склада, д. 10')
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertEquals('Новый адрес склада, д. 10', $warehouse->refresh()->address);
  }

}