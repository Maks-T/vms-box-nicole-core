<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Unit;
use Nicole\Box\Core\Filament\Resources\Units\Pages\ListUnits;
use Nicole\Box\Core\Filament\Resources\Units\Pages\CreateUnit;
use Nicole\Box\Core\Filament\Resources\Units\Pages\EditUnit;
use Livewire\Livewire;

class UnitResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка единиц измерения в таблице.
   */
  public function test_can_render_list_units_page(): void
  {
    $this->actingAs($this->adminUser);

    $unit = Unit::factory()->create(['slug' => 'm2']);

    Livewire::test(ListUnits::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$unit]);
  }

  /**
   * Сценарий: Создание новой единицы измерения через форму Filament.
   */
  public function test_can_create_unit_via_form(): void
  {
    $this->actingAs($this->adminUser);

    // Записываем данные напрямую в свойства Livewire-компонента
    Livewire::test(CreateUnit::class)
      ->set('data.name.ru', 'Литр')
      ->set('data.name.en', 'Liter')
      ->set('data.slug', 'liter')
      ->set('data.symbol.ru', 'л')
      ->set('data.symbol.en', 'l')
      ->set('data.code', '112')
      ->call('create')
      ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
      'slug' => 'liter',
      'code' => '112',
    ]);
  }

  /**
   * Сценарий: Проверка валидации полей (название и символ обязательны).
   */
  public function test_unit_fields_are_validated(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateUnit::class)
      ->set('data.name.ru', '')
      ->set('data.slug', 'test-unit')
      ->set('data.symbol.ru', '')
      ->call('create')
      ->assertHasFormErrors([
        'name.ru' => 'required',
        'symbol.ru' => 'required',
      ]);
  }

  /**
   * Сценарий: Редактирование существующей единицы измерения.
   */
  public function test_can_edit_unit_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $unit = Unit::factory()->create([
      'slug' => 'm3',
      'code' => '113',
    ]);

    Livewire::test(EditUnit::class, [
      'record' => $unit->getKey(),
    ])
      ->set('data.code', '114')
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertEquals('114', $unit->refresh()->code);
  }

}