<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas\PipelineRootForm;
use Nicole\Box\Core\Models\ProductVariant;

class ConfigureRootAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'configureRoot';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->modalHeading(function () {
            $livewire = $this->getLivewire();
            $variant = ProductVariant::find($livewire->base_variant_id);
            return __('Configuration Settings') . ": " . ($variant?->name ?? '');
        })
            ->modalWidth(Width::SevenExtraLarge)
            ->fillForm(function () {
                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                return PipelineRootForm::fill((int) $livewire->base_variant_id, $config['pipeline_code']);
            })
            ->schema(function (Schema $schema) {
                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                return PipelineRootForm::configure($schema, $config['pipeline_code'], (int) $livewire->base_variant_id);
            })
            ->action(function (array $data) {
                $livewire = $this->getLivewire();
                $config = $livewire->getPipelineConfig();
                PipelineRootForm::save($data, (int) $livewire->base_variant_id, $config['pipeline_code']);
            });
    }
}
