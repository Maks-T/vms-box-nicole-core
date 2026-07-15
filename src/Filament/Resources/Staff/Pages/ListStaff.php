<?php

namespace Nicole\Box\Core\Filament\Resources\Staff\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\Staff\StaffResource;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
