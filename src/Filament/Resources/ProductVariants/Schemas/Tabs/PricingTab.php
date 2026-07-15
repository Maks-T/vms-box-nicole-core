<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Tabs;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Nicole\Box\Core\Services\PricingManager;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Components\BaseCostSection;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\Components\PricesRepeater;

class PricingTab
{
  /**
   * Вспомогательный метод для обновления итоговой цены в строке репитера.
   */
  public static function updatePriceItem(Get $get, Set $set, string $inputCurrency, float $markup): void
  {
    $costPrice = (float) $get('../../cost_price');
    $costCurrency = $get('../../currency') ?? 'USD';

    if ($costPrice > 0) {
      $priceInCostCurrency = $costPrice * (1 + $markup / 100);
      $converted = app(PricingManager::class)->convert($priceInCostCurrency, $costCurrency, $inputCurrency);
      $set('desired_price', round($converted, 2));
    } else {
      $set('desired_price', 0.00);
    }
  }

  public static function make(): Tab
  {
    return Tab::make(__('Pricing & Economy'))
      ->icon('heroicon-o-banknotes')
      ->schema([
        BaseCostSection::make(),
        PricesRepeater::make(),
      ]);
  }

}
