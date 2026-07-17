<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\CreateProductVariant;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\EditProductVariant;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Tables\ProductVariantsTable;
use Nicole\Box\Core\Models\ProductVariant;
use Filament\Support\Enums\Width;

class ProductVariantResource extends Resource
{
  protected static ?string $model = ProductVariant::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

  protected static ?string $recordTitleAttribute = 'sku';

  protected static ?string $slug = 'product-variants';

  protected static ?int $navigationSort = 2;

  protected static Width $modalWidth = Width::Screen;

  public static function getNavigationGroup(): ?string
  {
    return __('Catalog');
  }

  public static function getModelLabel(): string
  {
    return __('Product Variant (SKU)');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Product Variants');
  }

  public static function form(Schema $schema): Schema
  {
    return ProductVariantForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return ProductVariantsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
  {
    return parent::getEloquentQuery()
      ->with([
        'product.category',
        'product.attributeValues.attribute',
        'product.attributeValues.option',
        'product.unit',
        'attributeValues.attribute',
        'attributeValues.option',
        'priceGroup',
      ])
      ->withSum('stocks as stocks_sum_quantity', 'quantity');
  }

  public static function getPages(): array
  {
    return [
      'index' => ListProductVariants::route('/'),
      'create' => CreateProductVariant::route('/create'),
      'edit' => EditProductVariant::route('/{record}/edit'),
    ];
  }
}
