<?php

declare(strict_types=1);

namespace Nicole\Box\Core;

use Filament\Contracts\Plugin;
use Filament\Panel;

class NicoleCorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'nicole-box-core';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Filament/Resources',
            for: 'Nicole\\Box\\Core\\Filament\\Resources',
        );

        $panel->discoverPages(
            in: __DIR__.'/Filament/Pages',
            for: 'Nicole\\Box\\Core\\Filament\\Pages',
        );

        $panel->discoverClusters(
            in: __DIR__.'/Filament/Clusters',
            for: 'Nicole\\Box\\Core\\Filament\\Clusters',
        );
    }

    public function boot(Panel $panel): void
    {
        // Logic to run after the panel is initialized
    }

    public static function make(): static
    {
        return new static;
    }
}
