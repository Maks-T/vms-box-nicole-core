<?php

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\ProductTypes\ProductTypeResource;

class EditProductType extends EditRecord
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
