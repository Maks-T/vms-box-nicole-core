<?php

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\ProductTypes\ProductTypeResource;

class ListProductTypes extends ListRecords
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
