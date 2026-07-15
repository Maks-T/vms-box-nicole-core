<?php

namespace Nicole\Box\Core\Filament\Resources\Categories;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\CreateCategory;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\EditCategory;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\ListCategories;
use Nicole\Box\Core\Filament\Resources\Categories\Schemas\CategoryForm;
use Nicole\Box\Core\Filament\Resources\Categories\Tables\CategoriesTable;
use Nicole\Box\Core\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'categories';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function getModelLabel(): string
    {
        return __('Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withDepth();
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
