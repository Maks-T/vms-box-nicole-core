<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\CreateProductType;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\EditProductType;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\ListProductTypes;
use Nicole\Box\Core\Filament\Resources\ProductTypes\RelationManagers\AttributesRelationManager;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Schemas\ProductTypeForm;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Tables\ProductTypesTable;
use Nicole\Box\Core\Models\ProductType;

class ProductTypeResource extends Resource
{
    protected static ?string $model = ProductType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'product-types';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Product Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Product Types');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [AttributesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductTypes::route('/'),
            'create' => CreateProductType::route('/create'),
            'edit' => EditProductType::route('/{record}/edit'),
        ];
    }
}
