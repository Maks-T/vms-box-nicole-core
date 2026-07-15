<?php

namespace Nicole\Box\Core\Filament\Resources\Products;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\Products\Pages\CreateProduct;
use Nicole\Box\Core\Filament\Resources\Products\Pages\EditProduct;
use Nicole\Box\Core\Filament\Resources\Products\Pages\ListProducts;
use Nicole\Box\Core\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use Nicole\Box\Core\Filament\Resources\Products\Schemas\ProductForm;
use Nicole\Box\Core\Filament\Resources\Products\Tables\ProductsTable;
use Nicole\Box\Core\Models\Product;

class ProductResource extends Resource
{
    use HasDynamicEavFields;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'products';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }


  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()
      ->with(['type.family', 'category', 'media', 'variants.media'])
      ->withCount('variants');
  }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [VariantsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
