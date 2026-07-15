<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;
use Nicole\Box\Core\Filament\Resources\Products\ProductResource;
use Filament\Notifications\Notification;
use Nicole\Box\Core\Models\ProductVariant;

class CreateProduct extends CreateRecord
{
    use HasDynamicEavFields;

    protected static string $resource = ProductResource::class;

  protected function afterCreate(): void
  {
    // Сохраняем динамические характеристики (EAV)
    $this->saveEavData($this->record, $this->data['eav'] ?? []);

    // Автоматически генерируем дефолтный вариант товара
    $product = $this->record;

    $pricingMode = $product->type?->pricing_mode ?? 'manual';
    $isManualPricing = $pricingMode !== 'complex_dictionary';

    // Создаем дефолтный SKU
    $variant = ProductVariant::create([
      'product_id' => $product->id,
      'sku' => $product->slug . '-def',
      'cost_price' => 0.00,
      'currency' => 'RUB',
      'is_default' => true,
      'is_active' => true,
      'is_manual_pricing' => $isManualPricing,
    ]);

    // Отправляем предупреждение
    Notification::make()
      ->warning()
      ->title(__('Default variant created automatically'))
      ->body(__('Please open the default variant configuration to set its cost price and warehouse stocks.'))
      ->persistent()
      ->send();
  }


}
