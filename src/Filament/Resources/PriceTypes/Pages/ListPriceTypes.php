<?php

namespace Nicole\Box\Core\Filament\Resources\PriceTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\PriceTypes\PriceTypeResource;

class ListPriceTypes extends ListRecords
{
    protected static string $resource = PriceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
