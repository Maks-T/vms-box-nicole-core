<?php

namespace Nicole\Box\Core\Filament\Resources\Warehouses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\Warehouses\WarehouseResource;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
