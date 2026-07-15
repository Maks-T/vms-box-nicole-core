<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use Nicole\Box\Core\Models\ProductVariant;

class TechnicalSpecsTab
{
  use HasDynamicEavFields;

  public static function make(): Tab
  {
    return Tab::make(__('Technical Specifications'))
      ->icon('heroicon-o-adjustments-vertical')
      ->schema(function (Get $get, ?Model $record, Component $livewire) {

        $product = ProductVariantForm::resolveProduct($get, $record, $livewire);

        $productType = $product?->product_type_id;

        return static::getDynamicEavSchema(
          $productType,
          new ProductVariant()->getMorphClass()
        );
      })
      ->columns(3);
  }

}
