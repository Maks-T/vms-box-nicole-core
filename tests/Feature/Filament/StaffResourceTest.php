<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use App\Models\User;
use Nicole\Box\Core\Models\Role;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\ListStaff;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\CreateStaff;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\EditStaff;
use Livewire\Livewire;

class StaffResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка сотрудников в таблице.
   */
  public function test_can_render_list_staff_page(): void
  {
    $this->actingAs($this->adminUser);

    $staffMember = User::factory()->create();

    Livewire::test(ListStaff::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$staffMember]);
  }

  /**
   * Сценарий: Создание нового сотрудника через форму Filament.
   */
  public function test_can_create_staff_member_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

    Livewire::test(CreateStaff::class)
      ->set('data.name', 'Иван Иванов')
      ->set('data.email', 'ivan@vms-box.local')
      ->set('data.password', 'secret_password')
      ->set('data.roles', [$role->id]) // Назначаем роль менеджеру
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем физическое появление сотрудника в СУБД
    $staff = User::where('email', 'ivan@vms-box.local')->first();
    $this->assertNotNull($staff);
    $this->assertTrue($staff->hasRole('manager'));
  }

  /**
   * Сценарий: Редактирование имени существующего сотрудника.
   */
  public function test_can_edit_staff_member_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $staffMember = User::factory()->create([
      'name' => 'Петр Петров',
    ]);

    Livewire::test(EditStaff::class, [
      'record' => $staffMember->getKey(),
    ])
      ->set('data.name', 'Петр Сидоров')
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertEquals('Петр Сидоров', $staffMember->refresh()->name);
  }

}