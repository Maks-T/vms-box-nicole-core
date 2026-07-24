<?php

namespace Nicole\Box\Core\Filament\Resources\Pipelines\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nicole\Box\Core\Filament\Clusters\Pipelines\PipelineCluster;
use Nicole\Box\Core\Filament\Resources\Pipelines\PipelineResource;

class EditPipeline extends EditRecord
{
    protected static string $resource = PipelineResource::class;

    public function getSubNavigation(): array
    {
        return PipelineCluster::getSubNavigationTabs();
    }

    public function getBreadcrumbs(): array
    {
        return [
            PipelineResource::getUrl('index') => __('Configurations'),
            $this->getRecordTitle() => null,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
