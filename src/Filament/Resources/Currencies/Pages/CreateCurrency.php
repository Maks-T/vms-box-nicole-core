<?php

namespace Nicole\Box\Core\Filament\Resources\Currencies\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Currencies\CurrencyResource;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
}
