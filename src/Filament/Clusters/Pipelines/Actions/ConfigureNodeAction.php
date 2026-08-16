<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Clusters\Pipelines\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nicole\Box\Core\Filament\Clusters\Pipelines\Schemas\PipelineRootForm;
use Nicole\Box\Core\Models\BindingRule;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class ConfigureNodeAction extends Action
{
  public static function getDefaultName(): ?string
  {
    return 'configureNode';
  }

  protected function resolveEntityInfo(array $arguments): array
  {
    if (!empty($arguments['entity_id'])) {
      return [
        'id' => (int)$arguments['entity_id'],
        'type' => (string)($arguments['entity_type'] ?? (new ProductVariant())->getMorphClass()),
      ];
    }

    if (!empty($arguments['variant_id'])) {
      return [
        'id' => (int)$arguments['variant_id'],
        'type' => (new ProductVariant())->getMorphClass(),
      ];
    }

    if (!empty($arguments['rule_id'])) {
      $rule = BindingRule::find($arguments['rule_id']);
      if ($rule && $rule->parent_id) {
        return [
          'id' => (int)$rule->parent_id,
          'type' => (string)$rule->parent_type,
        ];
      }
    }

    return ['id' => null, 'type' => (new ProductVariant())->getMorphClass()];
  }

  protected function setUp(): void
  {
    parent::setUp();

    $this->modalHeading(function (array $arguments) {
      $virtualMeta = $arguments['virtual_meta'] ?? [];
      if (!empty($virtualMeta)) {
        return __('Create Connection');
      }

      $info = $this->resolveEntityInfo($arguments);
      $morphClass = Relation::getMorphedModel($info['type']) ?? $info['type'];
      $entity = ($info['id'] && class_exists($morphClass)) ? $morphClass::find($info['id']) : null;
      $name = $entity ? PipelineEntityResolver::resolveName($entity, app()->getLocale()) : '';

      return __('Configure') . ": " . $name;
    })
      ->modalWidth(Width::SevenExtraLarge)
      ->fillForm(function (array $arguments) {
        $virtualMeta = $arguments['virtual_meta'] ?? [];
        if (!empty($virtualMeta)) {
          return [
            'child_type' => (new ProductVariant())->getMorphClass(),
            'child_id' => null,
            'quantity_formula' => '1',
            'static_meta' => [],
          ];
        }

        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        $info = $this->resolveEntityInfo($arguments);

        return PipelineRootForm::fill((int)($info['id'] ?? 0), $config['pipeline_code'], $info['type']);
      })
      ->schema(function (Schema $schema, array $arguments) {
        $virtualMeta = $arguments['virtual_meta'] ?? [];
        if (!empty($virtualMeta)) {
          return $schema->components([
            Grid::make(2)->schema([
              Select::make('child_type')
                ->label(__('Entity Type'))
                ->options(fn() => PipelineEntityResolver::getAvailableEntityTypes())
                ->default((new ProductVariant())->getMorphClass())
                ->required()
                ->live()
                ->afterStateUpdated(fn(Set $set) => $set('child_id', null))
                ->native(false),

              Select::make('child_id')
                ->label(__('Linked Item'))
                ->required()
                ->searchable()
                ->allowHtml()
                ->options(function (Get $get) use ($virtualMeta) {
                  $childType = (string)($get('child_type') ?? (new ProductVariant())->getMorphClass());
                  return PipelineEntityResolver::getEntitySelectOptions(
                    $childType,
                    $virtualMeta['type_code'] ?? null
                  );
                })
                ->native(false),

              Section::make(__('Advanced Settings'))
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                  Grid::make(2)->schema([
                    TextInput::make('quantity_formula')
                      ->label(__('Quantity Formula'))
                      ->default('1')
                      ->required(),

                    KeyValue::make('static_meta')
                      ->label(__('Technical Parameters'))
                      ->columnSpanFull(),
                  ])
                ])
            ])
          ]);
        }

        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        $info = $this->resolveEntityInfo($arguments);

        return PipelineRootForm::configure($schema, $config['pipeline_code'], (int)($info['id'] ?? 0), $info['type']);
      })
      ->action(function (array $data, array $arguments) {
        $virtualMeta = $arguments['virtual_meta'] ?? [];
        if (!empty($virtualMeta)) {
          $childType = (string)($data['child_type'] ?? (new ProductVariant())->getMorphClass());
          $childId = (int)$data['child_id'];

          BindingRule::create([
            'pipeline_id' => $virtualMeta['pipeline_id'] ?? null,
            'parent_type' => $virtualMeta['parent_type'],
            'parent_id' => $virtualMeta['parent_id'],
            'role' => $virtualMeta['role'],
            'child_type' => $childType,
            'child_id' => $childId,
            'quantity_formula' => $data['quantity_formula'] ?? '1',
            'static_meta' => $data['static_meta'] ?? null,
            'is_required' => true,
          ]);

          Notification::make()->title(__('Connection created successfully'))->success()->send();
          return;
        }

        $livewire = $this->getLivewire();
        $config = $livewire->getPipelineConfig();
        $info = $this->resolveEntityInfo($arguments);

        PipelineRootForm::save($data, (int)($info['id'] ?? 0), $config['pipeline_code'], $info['type']);
      });
  }
}
