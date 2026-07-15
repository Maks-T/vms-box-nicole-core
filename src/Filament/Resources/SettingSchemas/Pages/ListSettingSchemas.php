<?php

namespace Nicole\Box\Core\Filament\Resources\SettingSchemas\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\SettingSchemaResource;

class ListSettingSchemas extends ListRecords
{
    protected static string $resource = SettingSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
