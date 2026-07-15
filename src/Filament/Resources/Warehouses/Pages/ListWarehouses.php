<?php

namespace Nicole\Box\Core\Filament\Resources\Warehouses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\Warehouses\WarehouseResource;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
