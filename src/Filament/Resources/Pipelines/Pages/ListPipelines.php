<?php

namespace Nicole\Box\Core\Filament\Resources\Pipelines\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nicole\Box\Core\Filament\Resources\Pipelines\PipelineResource;

class ListPipelines extends ListRecords
{
    protected static string $resource = PipelineResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '' => __('Configurations'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
