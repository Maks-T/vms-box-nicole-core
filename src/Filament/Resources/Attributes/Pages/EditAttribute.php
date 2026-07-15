<?php

namespace Nicole\Box\Core\Filament\Resources\Attributes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\Attributes\AttributeResource;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
