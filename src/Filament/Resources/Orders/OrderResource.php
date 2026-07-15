<?php

namespace Nicole\Box\Core\Filament\Resources\Orders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Orders\Pages\CreateOrder;
use Nicole\Box\Core\Filament\Resources\Orders\Pages\EditOrder;
use Nicole\Box\Core\Filament\Resources\Orders\Pages\ListOrders;
use Nicole\Box\Core\Filament\Resources\Orders\Schemas\OrderForm;
use Nicole\Box\Core\Filament\Resources\Orders\Tables\OrdersTable;
use Nicole\Box\Core\Models\Order;
use Nicole\Box\Core\Filament\Resources\Orders\RelationManagers\SectionsRelationManager;
use Nicole\Box\Core\Filament\Resources\Orders\RelationManagers\ProductsRelationManager;

class OrderResource extends Resource
{
  protected static ?string $model = Order::class;

  // Меняем иконку на торговую сумку/корзину
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

  protected static ?string $recordTitleAttribute = 'id';

  protected static ?string $slug = 'orders';

  protected static ?int $navigationSort = 1;

  public static function getNavigationGroup(): ?string
  {
    return __('Sales');
  }

  public static function getModelLabel(): string
  {
    return __('Order');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Orders');
  }

  public static function form(Schema $schema): Schema
  {
    return OrderForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return OrdersTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      // Менеджер изделий заказа
      SectionsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListOrders::route('/'),
      'create' => CreateOrder::route('/create'),
      'edit' => EditOrder::route('/{record}/edit'),
    ];
  }
}
