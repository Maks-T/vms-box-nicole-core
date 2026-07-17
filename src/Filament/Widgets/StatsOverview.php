<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\Customer;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Stock;
use Nicole\Box\Core\Models\Warehouse;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use App\Models\User;

class StatsOverview extends BaseWidget
{
  protected int|string|array $columnSpan = 'full';

  protected function getColumns(): int
  {
    return 5;
  }

  protected function getStats(): array
  {
    $stats = Cache::remember('vms_dashboard_stats', now()->addHour(), function () {
      return [
        'products' => Product::count(),
        'variants' => ProductVariant::count(),
        'categories' => Category::count(),
        'warehouses' => Warehouse::count(),
        'price_types' => PriceType::count(),
        'stocks' => Stock::sum('quantity'),
        'customers' => Customer::count(),
        'orders' => Order::count(),
        'currencies' => Currency::count(),
        'attributes' => Attribute::count(),
        'staff' => User::count(),
        'price_groups' => PriceGroup::count(),
        'complex_dictionaries' => ComplexDictionary::count(),
        'families' => ProductFamily::count(),
        'types' => ProductType::count(),
      ];
    });

    return [
      // 1. Заказы
      Stat::make(__('Orders'), $stats['orders'] ?? 0)
        ->icon('heroicon-o-document-check')
        ->description(__('Total customer orders'))
        ->url('/admin/orders'),

      // 2. Покупатели
      Stat::make(__('Customers'), $stats['customers'] ?? 0)
        ->icon('heroicon-o-user-group')
        ->description(__('Registered client database'))
        ->url('/admin/customers'),

      // 3. Сотрудники
      Stat::make(__('Staff'), $stats['staff'] ?? 0)
        ->icon('heroicon-o-users')
        ->description(__('Staff members and managers'))
        ->url('/admin/staff'),

      // 4. Товары
      Stat::make(__('Products'), $stats['products'] ?? 0)
        ->icon('heroicon-o-shopping-bag')
        ->description(__('Total products in catalog'))
        ->url('/admin/products'),

      // 5. Модификации
      Stat::make(__('Product Variants'), $stats['variants'] ?? 0)
        ->icon('heroicon-o-tag')
        ->description(__('Unique product and service SKUs'))
        ->url('/admin/product-variants'),

      // 6. Семейства товаров
      Stat::make(__('Product Families'), $stats['families'] ?? 0)
        ->icon('heroicon-o-folder-open')
        ->description(__('High-level catalog branches'))
        ->url('/admin/product-families'),

      // 7. Типы товаров
      Stat::make(__('Product Types'), $stats['types'] ?? 0)
        ->icon('heroicon-o-queue-list')
        ->description(__('Assigned attribute schemas'))
        ->url('/admin/product-types'),

      // 8. Категории
      Stat::make(__('Categories'), $stats['categories'] ?? 0)
        ->icon('heroicon-o-folder')
        ->description(__('Catalog directories and sections'))
        ->url('/admin/categories'),

      // 9. Склады
      Stat::make(__('Warehouses'), $stats['warehouses'] ?? 0)
        ->icon('heroicon-o-home-modern')
        ->description(__('Physical inventory locations'))
        ->url('/admin/warehouses'),

      // 10. Общий остаток
      Stat::make(__('Total Stock'), number_format((float)($stats['stocks'] ?? 0), 0, '.', ' '))
        ->icon('heroicon-o-circle-stack')
        ->description(__('Total stock across all warehouses'))
        ->descriptionColor('success')
        ->url('/admin/product-variants'),

      // 11. Умные справочники
      Stat::make(__('Complex Dictionaries'), $stats['complex_dictionaries'] ?? 0)
        ->icon('heroicon-o-table-cells')
        ->description(__('Dynamic value multidimensional tables'))
        ->url('/admin/complex-dictionaries'),

      // 12. Атрибуты
      Stat::make(__('Attributes'), $stats['attributes'] ?? 0)
        ->icon('heroicon-o-adjustments-vertical')
        ->description(__('Assigned product properties'))
        ->url('/admin/attributes'),

      // 13. Ценовые группы
      Stat::make(__('Price Groups'), $stats['price_groups'] ?? 0)
        ->icon('heroicon-o-swatch')
        ->description(__('Base purchase cost margins'))
        ->url('/admin/price-groups'),

      // 14. Типы цен
      Stat::make(__('Price Types'), $stats['price_types'] ?? 0)
        ->icon('heroicon-o-currency-dollar')
        ->description(__('Active selling price lists'))
        ->url('/admin/price-types'),

      // 15. Валюты
      Stat::make(__('Currencies'), $stats['currencies'] ?? 0)
        ->icon('heroicon-o-globe-alt')
        ->description(__('Active system currencies'))
        ->url('/admin/сurrencies'),
    ];
  }
}
