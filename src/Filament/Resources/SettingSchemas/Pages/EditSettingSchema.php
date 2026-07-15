<?php

namespace Nicole\Box\Core\Filament\Resources\SettingSchemas\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\SettingSchemaResource;

class EditSettingSchema extends EditRecord
{
    protected static string $resource = SettingSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
