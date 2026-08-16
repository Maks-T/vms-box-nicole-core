<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas\PipelineRootForm;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

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
      $morphClass = Relation::getMorphedModel($livewire->base_entity_type) ?? $livewire->base_entity_type;
      $entity = class_exists($morphClass) ? $morphClass::find($livewire->base_entity_id) : null;
      $name = $entity ? PipelineEntityResolver::resolveName($entity, app()->getLocale()) : '';

      return __('Configuration Settings') . ": " . $name;
    })
      ->modalWidth(Width::SevenExtraLarge)
      ->fillForm(function () {
        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        return PipelineRootForm::fill((int)$livewire->base_entity_id, $config['pipeline_code'], $livewire->base_entity_type);
      })
      ->schema(function (Schema $schema) {
        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        return PipelineRootForm::configure($schema, $config['pipeline_code'], (int)$livewire->base_entity_id, $livewire->base_entity_type);
      })
      ->action(function (array $data) {
        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        PipelineRootForm::save($data, (int)$livewire->base_entity_id, $config['pipeline_code'], $livewire->base_entity_type);
      });
  }
}
