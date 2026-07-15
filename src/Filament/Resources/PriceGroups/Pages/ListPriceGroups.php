<?php

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\PriceGroups\PriceGroupResource;

class ListPriceGroups extends ListRecords
{
    protected static string $resource = PriceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
