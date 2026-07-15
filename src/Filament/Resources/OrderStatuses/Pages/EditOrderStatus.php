<?php

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\OrderStatusResource;

class EditOrderStatus extends EditRecord
{
    protected static string $resource = OrderStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
