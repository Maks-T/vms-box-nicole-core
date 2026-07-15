<?php

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\ProductTypes\ProductTypeResource;

class CreateProductType extends CreateRecord
{
    protected static string $resource = ProductTypeResource::class;
}
