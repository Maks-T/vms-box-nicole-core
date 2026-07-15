<?php

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\ComplexDictionaryResource;

class EditComplexDictionary extends EditRecord
{
    protected static string $resource = ComplexDictionaryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
