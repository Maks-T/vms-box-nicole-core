<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Pages\PipelineChainsPage;
use Nicole\Box\Core\Filament\Resources\Pipelines\PipelineResource;

class PipelineCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /**
     * Динамический реестр отраслевых вкладок
     * @var array<int, \Closure|NavigationItem>
     */
    protected static array $customNavigationTabs = [];

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
        return __('Configurations');
    }

    public static function registerNavigationTab(\Closure|NavigationItem $item): void
    {
        static::$customNavigationTabs[] = $item;
    }

    public static function getSubNavigationTabs(): array
    {
        $items = [
            NavigationItem::make(__('Pipeline Schemas'))
                ->url(PipelineResource::getUrl('index'))
                ->icon(PipelineResource::getNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(PipelineResource::getRouteBaseName() . '.*')),

            NavigationItem::make(__('Chains & Visual Tree'))
                ->url(PipelineChainsPage::getUrl())
                ->icon(PipelineChainsPage::getNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(PipelineChainsPage::getRouteName())),
        ];

        foreach (static::$customNavigationTabs as $customTab) {
            $resolved = $customTab instanceof \Closure ? $customTab() : $customTab;
            if ($resolved instanceof NavigationItem) {
                $items[] = $resolved;
            }
        }

        return $items;
    }
}
