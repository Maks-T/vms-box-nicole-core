<?php

namespace Nicole\Box\Core\Filament\Resources\Units\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\Units\UnitResource;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
