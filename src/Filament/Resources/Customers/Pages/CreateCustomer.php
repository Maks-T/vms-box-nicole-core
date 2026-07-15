<?php

namespace Nicole\Box\Core\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Customers\CustomerResource;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
