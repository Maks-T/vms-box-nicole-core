<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Traits;

use App\Models\User;
use Nicole\Box\Core\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithFilamentAdmin
{
  protected User $adminUser;

  /**
   * Инициализирует администратора панели и сбрасывает кэш разрешений.
   */
  protected function setUpFilamentAdmin(): void
  {
    $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);

    // Очищаем кэш и заставляем Spatie перерегистрировать права в Gate
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->registerPermissions($gate);

    // Принудительно регистрируем перехватчик для роли admin прямо в тесте
    $gate->before(function ($user, $ability) {
      return $user->hasRole('admin') ? true : null;
    });

    // Создаем администратора панели
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    /** @var User $user */
    $user = User::factory()->create();

    $this->adminUser = $user;
    $this->adminUser->assignRole($adminRole);
  }

}
