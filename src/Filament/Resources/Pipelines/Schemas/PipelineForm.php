<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Pipelines\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Support\Constants\EntityType as ET;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;
use Nicole\Box\Core\Support\Pipelines\PipelineRoleResolver;
use Nicole\Box\Core\Support\Pipelines\PipelineSchemaTransformer;

class PipelineForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('PipelineTabs')
        ->tabs([
          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-information-circle')
            ->schema([

              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                      if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                        $set('code', Str::slug((string)$state, '_'));
                        $set('slug', Str::slug((string)$state, '-'));
                      }
                    })
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(table: 'pipelines', column: 'code', ignoreRecord: true)
                    ->alphaDash(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(table: 'pipelines', column: 'slug', ignoreRecord: true)
                    ->alphaDash()
                    ->helperText(__('Used for clean URLs (SEO)')),

                  TextInput::make('sort_order')
                    ->label(__('Sort Order'))
                    ->numeric()
                    ->default(0),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->columnSpanFull(),
                ])
                ->columns(2),
            ]),

          Tabs\Tab::make(__('Pipeline Schema Builder'))
            ->icon('heroicon-o-rectangle-group')
            ->schema([
              Section::make(__('Pipeline Schema Builder'))
                ->description(__('Configure parent product types and their allowed slots / dependencies'))
                ->schema([

                  Repeater::make('schema')
                    ->hiddenLabel()
                    ->addActionLabel(__('Add Parent Group'))
                    ->reorderable()
                    ->collapsible()
                    ->collapsed()
                    ->defaultItems(0)
                    ->itemLabel(fn(array $state) => PipelineSchemaTransformer::resolveGroupLabel($state))
                    ->formatStateUsing(fn($state) => PipelineSchemaTransformer::toFormState($state))
                    ->dehydrateStateUsing(fn($state) => PipelineSchemaTransformer::toDatabase($state))
                    ->schema([
                      // Выбор родительской группы
                      Select::make('parent_code')
                        ->label(__('Parent Group / Type'))
                        ->options(function () {
                          $locale = app()->getLocale();
                          $productTypes = ProductType::where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn($t) => [$t->code => $t->getTranslation('name', $locale) ?: $t->name])
                            ->toArray();

                          $dictionaries = ComplexDictionary::where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn($d) => [$d->code => $d->getTranslation('name', $locale) ?: $d->name])
                            ->toArray();

                          return $productTypes + $dictionaries;
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpanFull(),

                      // Вложенный репитер слотов
                      Repeater::make('slots')
                        ->label(__('Slots & Dependencies'))
                        ->addActionLabel(__('Add Slot'))
                        ->reorderable()
                        ->collapsible()
                        ->collapsed(false)
                        ->defaultItems(0)
                        ->schema([
                          Select::make('role_code')
                            ->label(__('Role Code'))
                            ->options(fn() => PipelineRoleResolver::getOptions())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                              if (!$state) return;

                              $label = PipelineRoleResolver::getLabel($state);
                              $set('label_key', ['ru' => $label, 'en' => $label]);

                              $defaultTarget = PipelineRoleResolver::getDefaultTarget($state);
                              if ($defaultTarget) {
                                $set('target_type', $defaultTarget['target_type'] ?? ET::PRODUCT_TYPE);
                                $set('target_code', $defaultTarget['target_code'] ?? null);
                              }
                            })
                            ->native(false),

                          TextInput::make('label_key')
                            ->label(__('Label Key (e.g. Start Clip)'))
                            ->required()
                            ->translatable(),

                          Select::make('target_type')
                            ->label(__('Target Entity'))
                            ->options(fn() => PipelineEntityResolver::getTargetEntityOptions())
                            ->default(ET::PRODUCT_TYPE)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('target_code', null))
                            ->native(false),

                          Select::make('target_code')
                            ->label(__('Target Code'))
                            ->required(fn(Get $get) => $get('target_type') !== ET::SCALAR)
                            ->visible(fn(Get $get) => $get('target_type') !== ET::SCALAR)
                            ->options(fn(Get $get) => PipelineEntityResolver::getTargetCodeOptions($get('target_type')))
                            ->searchable()
                            ->native(false),

                          Toggle::make('is_required')
                            ->label(__('Is Required'))
                            ->default(false),

                          Toggle::make('is_multiple')
                            ->label(__('Is Multiple'))
                            ->default(false),
                        ])
                        ->columns(6)
                        ->columnSpanFull()
                    ])
                ])
            ]),

          SalesChannelsTab::make('pipeline'),
        ])
        ->columnSpanFull()
    ]);
  }
}
