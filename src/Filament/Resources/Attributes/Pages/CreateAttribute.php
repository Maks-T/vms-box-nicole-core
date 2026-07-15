<?php

namespace Nicole\Box\Core\Filament\Resources\Attributes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Attributes\AttributeResource;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;
}
