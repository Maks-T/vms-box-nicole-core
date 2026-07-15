<?php

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\PriceGroups\PriceGroupResource;

class EditPriceGroup extends EditRecord
{
    protected static string $resource = PriceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
