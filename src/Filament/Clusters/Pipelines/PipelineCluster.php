<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class PipelineCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 3;

    // Горизонтальные верхние вкладки навигации
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('Configurations & Chains');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('Configurations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }
}
