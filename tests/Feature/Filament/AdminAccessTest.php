<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use App\Models\User;
use Nicole\Box\Core\Models\Role;

class AdminAccessTest extends TestCase
{
  use LazilyRefreshDatabase;

  // Быстрая миграция БД в изолированной транзакции

  protected function setUp(): void
  {
    parent::setUp();

    // Сбрасываем кэш разрешений Spatie перед каждым тестом, чтобы не было наложения
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
  }

  /**
   * Сценарий 1: Гость должен быть перенаправлен на страницу авторизации при попытке открыть `/admin`
   */
  public function test_guest_is_redirected_to_login_page(): void
  {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
  }

  /**
   * Сценарий 2: Авторизованный пользователь имеет базовый доступ к панели управления `/admin`
   */
  public function test_authenticated_user_can_access_admin_panel(): void
  {
    // Создаем обычного пользователя
    $user = User::factory()->create();

    // Открываем панель под этим пользователем
    $response = $this->actingAs($user)->get('/admin');

    $response->assertSuccessful();
  }

  /**
   * Сценарий 3: Пользователь с ролью 'admin' может просматривать список сотрудников
   */
  public function test_admin_user_can_view_staff_resource(): void
  {
    // Создаем роль admin для Spatie [2]
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole($adminRole);

    // Пользователь с ролью admin должен успешно открыть страницу списка сотрудников
    $response = $this->actingAs($user)->get('/admin/staff');

    $response->assertSuccessful();
  }

}
