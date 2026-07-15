<?php

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\ComplexDictionaryResource;

class ListComplexDictionaries extends ListRecords
{
    protected static string $resource = ComplexDictionaryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
