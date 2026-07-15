<?php

namespace Nicole\Box\Core\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
