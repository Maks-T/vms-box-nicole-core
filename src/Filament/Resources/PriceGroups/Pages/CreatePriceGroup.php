<?php

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\PriceGroups\PriceGroupResource;

class CreatePriceGroup extends CreateRecord
{
    protected static string $resource = PriceGroupResource::class;
}
