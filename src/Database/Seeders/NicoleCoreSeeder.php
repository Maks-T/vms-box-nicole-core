<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Role;
use Nicole\Box\Core\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class NicoleCoreSeeder extends Seeder
{
  public function run(): void
  {

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $models = [
      'Product', 'ProductVariant', 'Category', 'ProductFamily', 'ProductType',
      'Attribute', 'ComplexDictionary', 'PriceGroup', 'Unit', 'Currency',
      'Warehouse', 'PriceType', 'SettingSchema', 'Order', 'Customer', 'OrderStatus',
      'User', 'Staff', 'Role'
    ];

    $actions = [
      'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
      'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Replicate', 'Reorder'
    ];


    Permission::firstOrCreate(['name' => 'page_Dashboard', 'guard_name' => 'web']);

    foreach ($models as $model) {
      foreach ($actions as $action) {
        Permission::firstOrCreate([
          'name' => "{$action}:{$model}",
          'guard_name' => 'web',
        ]);
      }
    }

    $dashboardPermissions = Permission::where('name', 'like', '%Dashboard%')
      ->orWhere('name', 'like', '%Widget%')
      ->pluck('name')
      ->toArray();

    $adminRole = Role::firstOrCreate(
      ['name' => 'admin'],
      ['guard_name' => 'web']
    );

    $adminUser = User::firstOrCreate(
      ['email' => 'admin@vms.local'],
      [
        'name' => 'System Administrator',
        'password' => 'password'
      ]
    );
    $adminUser->assignRole($adminRole);


    // Настройка роли Контент-менеджера (Управление каталогом и его настройками)
    $contentManagerRole = Role::firstOrCreate(
      ['name' => 'content_manager'],
      ['guard_name' => 'web']
    );

    // Список сущностей, к которым контент-менеджеру нужен полный доступ
    $catalogModels = [
      'Product',
      'ProductVariant',
      'Category',
      'ProductFamily',
      'ProductType',
      'Attribute',
      'ComplexDictionary',
      'PriceGroup',
      'Unit',
      'Currency',
      'Warehouse',
      'PriceType',
      'SettingSchema'
    ];

    $contentPermissions = Permission::where(function ($query) use ($catalogModels) {
      foreach ($catalogModels as $model) {
        $query->orWhere('name', 'like', "%:{$model}");
      }
    })->pluck('name')->toArray();

    // Объединяем права каталога с правами на доступ к панели (Dashboard)
    $finalContentPermissions = array_merge($contentPermissions, $dashboardPermissions);
    $contentManagerRole->syncPermissions($finalContentPermissions);

    // Создаем дефолтного пользователя для контент-менеджера
    $contentUser = User::firstOrCreate(
      ['email' => 'content@vms.local'],
      [
        'name' => 'Контент-менеджер',
        'password' => 'password'
      ]
    );

    $contentUser->assignRole($contentManagerRole);


    // Настройка роли Дилера (Менеджера) (Только виджет, заказы и клиенты)
    $dealerRole = Role::firstOrCreate(
      ['name' => 'dealer'],
      ['guard_name' => 'web']
    );

    // Находим права для Заказов и Клиентов
    $dealerModels = ['Order', 'Customer'];
    $dealerPermissions = Permission::where(function ($query) use ($dealerModels) {
      foreach ($dealerModels as $model) {
        $query->orWhere('name', 'like', "%:{$model}");
      }
    })->pluck('name')->toArray();

    // Добавляем только права на просмотр статусов заказов (без возможности их изменения)
    $statusReadPermissions = Permission::where(function ($query) {
      $query->where('name', 'View:OrderStatus')
        ->orWhere('name', 'ViewAny:OrderStatus');
    })->pluck('name')->toArray();

    // Объединяем права на заказы с правами на просмотр статусов и доступ к панели (Dashboard)
    $finalDealerPermissions = array_merge($dealerPermissions, $statusReadPermissions, $dashboardPermissions);
    $dealerRole->syncPermissions($finalDealerPermissions);

    // Создаем дефолтного пользователя Дилера (Менеджера)
    $dealerUser = User::firstOrCreate(
      ['email' => 'dealer@vms.local'],
      [
        'name' => 'Дилер (Менеджер)',
        'password' => 'password'
      ]
    );
    $dealerUser->assignRole($dealerRole);

    $this->command->info(
      'Nicole Core: Roles and default users synced successfully with Shield permissions:' . PHP_EOL .
      '  - admin@vms.local / password (Super Admin)' . PHP_EOL .
      '  - content@vms.local / password (Content Manager)' . PHP_EOL .
      '  - dealer@vms.local / password (Dealer / Manager)'
    );
  }
}
