<?php

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages\CreateOrderStatus;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages\EditOrderStatus;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages\ListOrderStatuses;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\Schemas\OrderStatusForm;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\Tables\OrderStatusesTable;
use Nicole\Box\Core\Models\OrderStatus;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class OrderStatusResource extends Resource
{
  use Translatable;

  protected static ?string $model = OrderStatus::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

  protected static ?string $recordTitleAttribute = 'name';

  protected static ?string $slug = 'order-statuses';

  protected static ?int $navigationSort = 11;

  public static function getNavigationGroup(): ?string
  {
    return __('Catalog Settings');
  }

  public static function getModelLabel(): string
  {
    return __('Order Status');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Order Statuses');
  }

  public static function form(Schema $schema): Schema
  {
    return OrderStatusForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return OrderStatusesTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index' => ListOrderStatuses::route('/'),
      'create' => CreateOrderStatus::route('/create'),
      'edit' => EditOrderStatus::route('/{record}/edit'),
    ];
  }
}
