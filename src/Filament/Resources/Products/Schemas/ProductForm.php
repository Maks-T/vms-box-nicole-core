<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Forms\Tabs\LinkedItemsTab;
use Nicole\Box\Core\Filament\Forms\Tabs\MediaGalleryTab;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Support\Constants\CatalogType;
use Njxqlus\Filament\Components\Forms\RelationManager as NjxqlusRelationManager;
use Nicole\Box\Core\Filament\Resources\Products\RelationManagers\VariantsRelationManager;

class ProductForm
{
  use HasDynamicEavFields;

  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('ProductData')
        ->tabs([

          Tabs\Tab::make(__('Product Variants'))
            ->icon('heroicon-o-tag')
            ->schema([
              NjxqlusRelationManager::make()
                ->manager(VariantsRelationManager::class)
                ->lazy(false)
            ]),


          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-information-circle')
            ->schema([
              Grid::make(3)->schema([
                Section::make(__('Identity'))
                  ->columnSpan(2)
                  ->schema([
                    TextInput::make('name')
                      ->label(__('Name'))
                      ->required()
                      ->live(onBlur: true)
                      ->afterStateUpdated(
                        FormHelper::generateSlug('slug', '-', false),
                      )
                      ->translatable(),

                    TextInput::make('slug')
                      ->label(__('Slug'))
                      ->required()
                      ->unique(Product::class, 'slug', ignoreRecord: true),

                    Textarea::make('short_description')
                      ->label(__('Short Description'))
                      ->rows(2)
                      ->columnSpanFull()
                      ->translatable(),

                    Textarea::make('description')
                      ->label(__('Description'))
                      ->rows(4)
                      ->columnSpanFull()
                      ->translatable(),
                  ]),

                Section::make(__('Classification'))
                  ->columnSpan(1)
                  ->schema([
                    Toggle::make('is_active')
                      ->label(__('Is Active'))
                      ->default(true),

                    Select::make('catalog_type')
                      ->label(__('Catalog Type'))
                      ->options(CatalogType::options())
                      ->default(CatalogType::PRODUCT)
                      ->required(),

                    // 1. Виртуальное поле "Семейство"
                    Select::make('family_id')
                      ->label(__('Product Family'))
                      ->options(ProductFamily::pluck('name', 'id'))
                      ->live()
                      ->formatStateUsing(fn($record) => $record?->type?->family_id)
                      ->afterStateUpdated(fn(Set $set) => $set('product_type_id', null))
                      ->dehydrated(false),

                    // 2. Поле "Тип товара" (Фильтруется по семейству)
                    Select::make('product_type_id')
                      ->label(__('Product Type'))
                      ->options(function (Get $get) {
                        $familyId = $get('family_id');
                        if (!$familyId) {
                          return ProductType::pluck('name', 'id');
                        }
                        return ProductType::where('family_id', $familyId)->pluck('name', 'id');
                      })
                      ->required()
                      ->live()
                      ->afterStateUpdated(fn(Set $set) => $set('eav', [])),

                    SelectTree::make('category_id')
                      ->label(__('Category'))
                      ->relationship('category', 'name', 'parent_id')
                      ->enableBranchNode()
                      ->searchable(),

                    Select::make('unit_id')
                      ->label(__('Unit'))
                      ->relationship('unit', 'name')
                      ->searchable()
                      ->preload(),
                  ]),
              ]),
            ]),


          Tabs\Tab::make(__('Technical Specifications'))
            ->icon('heroicon-o-adjustments-vertical')
            ->schema(
              fn($get) => static::getDynamicEavSchema(
                (int)$get('product_type_id'),
                new Product()->getMorphClass(),
              ),
            ),


          MediaGalleryTab::make(),


          LinkedItemsTab::make(),


          SalesChannelsTab::make('product'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
