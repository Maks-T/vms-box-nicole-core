<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\Schemas\Tabs;

use Filament\Schemas\Components\Tabs\Tab;
use Nicole\Box\Core\Models\OrderSection;
use Nicole\Box\Core\Support\OrderSectionFormatterResolver;

class EstimateTab
{
  /**
   * Вкладка сметы делегирована отраслевому резолверу.
   *
   * @since 2026-09-06
   */
  public static function make(): Tab
  {
    return Tab::make(__('Estimate'))
      ->icon('heroicon-o-currency-dollar')
      ->schema(fn(OrderSection $record) => OrderSectionFormatterResolver::formatEstimate($record));
  }

}