<?php

namespace Nicole\Box\Core\Filament\Resources\Pipelines\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nicole\Box\Core\Filament\Clusters\Pipelines\PipelineCluster;
use Nicole\Box\Core\Filament\Resources\Pipelines\PipelineResource;

class CreatePipeline extends CreateRecord
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
            __('Create') => null,
        ];
    }
}
