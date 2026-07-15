<?php

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\OrderStatusResource;

class ListOrderStatuses extends ListRecords
{
    protected static string $resource = OrderStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
