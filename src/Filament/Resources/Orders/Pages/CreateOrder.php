<?php

namespace Nicole\Box\Core\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Orders\OrderResource;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
