<?php

namespace Nicole\Box\Core\Filament\Resources\PriceTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\PriceTypes\PriceTypeResource;

class CreatePriceType extends CreateRecord
{
    protected static string $resource = PriceTypeResource::class;
}
