<?php

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\OrderStatuses\OrderStatusResource;

class CreateOrderStatus extends CreateRecord
{
    protected static string $resource = OrderStatusResource::class;
}
