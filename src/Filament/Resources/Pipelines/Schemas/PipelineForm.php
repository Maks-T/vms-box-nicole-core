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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nicole\Box\Core\DTO\Pipeline\PipelineSlotDto;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Pipelines\PipelineRoleResolver;

class PipelineForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components(function (?Model $record) {
      $pipelineCode = $record instanceof Pipeline ? $record->code : '';

      $treeService = app(PipelineTreeService::class);
      $pipelineSlots = $treeService->getPipelineSlots((string)$pipelineCode, $record);
      $parentTypeCodes = collect($pipelineSlots)->keys()->toArray();

      if (empty($parentTypeCodes)) {
        $parentTypeCodes = ProductType::pluck('code')->toArray();
      }

      $sections = [];
      $locale = app()->getLocale();

      foreach ($parentTypeCodes as $parentTypeCode) {

        $productType = ProductType::where('code', $parentTypeCode)->first();
        $dictionary = ComplexDictionary::where('code', $parentTypeCode)->first();

        $title = $productType?->getTranslation('name', $locale)
          ?? ($dictionary?->getTranslation('name', $locale) ?? ucfirst($parentTypeCode));

        $sections[] = Section::make($title)
          ->icon('heroicon-o-folder-open')
          ->collapsed()
          ->schema([
            Repeater::make("schema.{$parentTypeCode}")
              ->hiddenLabel()
              ->formatStateUsing(function ($state) {
                if (!is_array($state)) {
                  return [];
                }

                $formatted = [];
                foreach ($state as $key => $item) {
                  if (is_array($item)) {
                    if (is_string($key) && empty($item['role_code'])) {
                      $item['role_code'] = $key;
                    }

                    $typeCode = $item['type_code'] ?? 'general';
                    if ($typeCode === 'general') {
                      $item['target_entity_type'] = 'general';
                    } elseif (ComplexDictionary::where('code', $typeCode)->exists()) {
                      $item['target_entity_type'] = 'complex_dictionary';
                    } else {
                      $item['target_entity_type'] = 'product_type';
                    }

                    $formatted[] = $item;
                  }
                }
                return $formatted;
              })
              ->dehydrateStateUsing(function ($state) {
                if (!is_array($state)) {
                  return [];
                }

                $result = [];
                foreach ($state as $item) {
                  $roleCode = $item['role_code'] ?? null;
                  if ($roleCode) {

                    if (($item['target_entity_type'] ?? '') === 'general') {
                      $item['type_code'] = 'general';
                    }
                    unset($item['target_entity_type']);

                    $result[$roleCode] = $item;
                  }
                }
                return $result;
              })
              ->schema([
                TextInput::make('role_code')
                  ->label(__('Role Code'))
                  ->required()
                  ->alphaDash()
                  ->datalist(fn() => array_keys(PipelineRoleResolver::getOptions())),

                TextInput::make('label_key')
                  ->label(__('Label Key (e.g. Start Clip)'))
                  ->required()
                  ->translatable(),

                Select::make('target_entity_type')
                  ->label(__('Target Entity'))
                  ->options([
                    'product_type' => __('Product Type'),
                    'complex_dictionary' => __('Complex Dictionary'),
                    'general' => __('Technical Parameter (Scalar)'),
                  ])
                  ->default('product_type')
                  ->required()
                  ->live()
                  ->afterStateUpdated(fn(Set $set) => $set('type_code', null))
                  ->native(false),

                Select::make('type_code')
                  ->label(__('Target Code'))
                  ->required(fn(Get $get) => $get('target_entity_type') !== 'general')
                  ->visible(fn(Get $get) => $get('target_entity_type') !== 'general')
                  ->options(function (Get $get) {
                    $targetType = $get('target_entity_type') ?? 'product_type';

                    if ($targetType === 'complex_dictionary') {
                      return ComplexDictionary::where('is_active', true)->pluck('name', 'code')->toArray();
                    }

                    return ProductType::where('is_active', true)->pluck('name', 'code')->toArray();
                  })
                  ->searchable()
                  ->native(false),

                Toggle::make('is_required')
                  ->label(__('Is Required'))
                  ->default(false),

                Toggle::make('is_multiple')
                  ->label(__('Is Multiple (Folder)'))
                  ->default(false),
              ])
              ->columns(6)
              ->addActionLabel(__('Add Slot'))
          ]);
      }

      return [
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
                          $set('code', Str::slug($state, '_'));
                          $set('slug', Str::slug($state, '-'));
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
                  ->schema($sections)
              ]),

            SalesChannelsTab::make('pipeline'),
          ])
          ->columnSpanFull()
      ];
    });
  }
}
