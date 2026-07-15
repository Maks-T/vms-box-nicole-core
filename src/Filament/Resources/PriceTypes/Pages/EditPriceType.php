<?php

namespace Nicole\Box\Core\Filament\Resources\PriceTypes\Pages;

use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Filament\Resources\PriceTypes\PriceTypeResource;

class EditPriceType extends EditRecord
{
    protected static string $resource = PriceTypeResource::class;

  protected function getHeaderActions(): array
  {
    return [
      ProtectDefaultRecord::pageDeleteAction('Cannot delete default record'),
    ];
  }
}
