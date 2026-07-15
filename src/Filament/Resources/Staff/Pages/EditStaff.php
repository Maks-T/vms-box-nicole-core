<?php

namespace Nicole\Box\Core\Filament\Resources\Staff\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\Staff\StaffResource;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
