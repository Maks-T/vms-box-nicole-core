<?php

namespace Nicole\Box\Core\Filament\Resources\Warehouses;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\EditWarehouse;
use Nicole\Box\Core\Filament\Resources\Warehouses\Pages\ListWarehouses;
use Nicole\Box\Core\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use Nicole\Box\Core\Filament\Resources\Warehouses\Tables\WarehousesTable;
use Nicole\Box\Core\Models\Warehouse;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'warehouses';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Warehouse');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Warehouses');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
