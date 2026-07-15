<?php

namespace Nicole\Box\Core\Filament\Resources\Units\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\Units\UnitResource;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
