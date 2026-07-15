<?php

namespace Nicole\Box\Core\Filament\Resources\Customers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Customers\Pages\CreateCustomer;
use Nicole\Box\Core\Filament\Resources\Customers\Pages\EditCustomer;
use Nicole\Box\Core\Filament\Resources\Customers\Pages\ListCustomers;
use Nicole\Box\Core\Filament\Resources\Customers\Schemas\CustomerForm;
use Nicole\Box\Core\Filament\Resources\Customers\Tables\CustomersTable;
use Nicole\Box\Core\Models\Customer;

class CustomerResource extends Resource
{
  protected static ?string $model = Customer::class;

  // Меняем иконку на группу пользователей
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

  protected static ?string $recordTitleAttribute = 'phone';

  protected static ?string $slug = 'customers';

  protected static ?int $navigationSort = 3;

  public static function getNavigationGroup(): ?string
  {
    return __('Sales');
  }

  public static function getModelLabel(): string
  {
    return __('Customer');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Customers');
  }

  public static function form(Schema $schema): Schema
  {
    return CustomerForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return CustomersTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      // ToDo в будущем можно будет вывести список всех заказов этого покупателя
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListCustomers::route('/'),
      'create' => CreateCustomer::route('/create'),
      'edit' => EditCustomer::route('/{record}/edit'),
    ];
  }
}
