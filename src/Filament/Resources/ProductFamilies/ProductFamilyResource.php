<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductFamilies;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\CreateProductFamily;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\EditProductFamily;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\ListProductFamilies;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Schemas\ProductFamilyForm;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Tables\ProductFamiliesTable;
use Nicole\Box\Core\Models\ProductFamily;

class ProductFamilyResource extends Resource
{
    protected static ?string $model = ProductFamily::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'product-families';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Product Family');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Product Families');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductFamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductFamiliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // ToDO добавить RelationManager для просмотра связанных ProductTypes
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductFamilies::route('/'),
            'create' => CreateProductFamily::route('/create'),
            'edit' => EditProductFamily::route('/{record}/edit'),
        ];
    }
}
