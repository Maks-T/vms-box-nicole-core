<?php

namespace Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\ProductFamilyResource;

class EditProductFamily extends EditRecord
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
