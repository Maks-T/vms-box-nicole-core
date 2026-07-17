<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Forms\Tabs\MediaGalleryTab;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\IdentityTab;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\TechnicalSpecsTab;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\PricingTab;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs\InventoryTab;

class ProductVariantForm
{
  use HasDynamicEavFields;

  /**
   * Универсальный метод для надежного определения родительского товара (Product)
   * в любом контексте (страница, модальное окно RelationManager, репитер).
   */
  public static function resolveProduct(Get $get, ?Model $record, ?Component $livewire): ?Product
  {
    if ($livewire instanceof RelationManager) {
      $owner = $livewire->getOwnerRecord();
      if ($owner instanceof Product) {
        return $owner;
      }
    }

    if ($record instanceof ProductVariant) {
      return $record->product;
    }

    if ($record instanceof \Nicole\Box\Core\Models\ProductVariantPrice && $record->variant) {
      return $record->variant->product;
    }

    $productId = $get('product_id') ?? $get('../../product_id');
    if ($productId) {
      return Product::with('type')->find($productId);
    }

    return null;
  }

  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('VariantData')
        ->tabs([
          PricingTab::make(),
          IdentityTab::make(),
          TechnicalSpecsTab::make(),
          InventoryTab::make(),
          MediaGalleryTab::make(),
          SalesChannelsTab::make('product_variant'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
