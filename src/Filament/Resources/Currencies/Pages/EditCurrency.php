<?php

namespace Nicole\Box\Core\Filament\Resources\Currencies\Pages;

use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Filament\Resources\Currencies\CurrencyResource;

class EditCurrency extends EditRecord
{
  protected static string $resource = CurrencyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ProtectDefaultRecord::pageDeleteAction('Cannot delete default record'),
    ];
  }
}
