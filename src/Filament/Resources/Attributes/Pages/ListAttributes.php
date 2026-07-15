<?php

namespace Nicole\Box\Core\Filament\Resources\Attributes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\Attributes\AttributeResource;

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
