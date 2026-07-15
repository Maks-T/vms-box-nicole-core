<?php

namespace Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\ProductFamilyResource;

class ListProductFamilies extends ListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
