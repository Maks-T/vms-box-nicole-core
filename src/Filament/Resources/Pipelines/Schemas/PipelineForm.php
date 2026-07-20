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
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Calculator\PipelineRoleResolver;

class PipelineForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components(function (?Model $record) {
      $pipelineCode = $record instanceof Pipeline ? $record->code : '';

      $pipelineSchema = app(PipelineTreeService::class)->getPipelineSchema((string)$pipelineCode, $record);
      $parentTypeCodes = collect($pipelineSchema)->keys()->toArray();

      if (empty($parentTypeCodes)) {
        $industry = $record instanceof Pipeline ? $record->industry : null;
        $parentTypeCodes = $industry
          ? ProductType::whereHas('family', fn($q) => $q->where('code', $industry))->pluck('code')->toArray()
          : [];
      }

      $sections = [];
      $locale = app()->getLocale();

      foreach ($parentTypeCodes as $parentTypeCode) {
        $productType = ProductType::where('code', $parentTypeCode)->first();
        $title = $productType?->getTranslation('name', $locale) ?? ucfirst($parentTypeCode);

        $sections[] = Section::make($title)
          ->icon('heroicon-o-folder-open')
          ->collapsed()
          ->schema([
            Repeater::make("schema.{$parentTypeCode}")
              ->hiddenLabel()
              ->schema([
                TextInput::make('role_code')
                  ->label(__('Role Code'))
                  ->required()
                  ->alphaDash()
                  ->datalist(function (Get $get) {
                    $industry = $get('../../../industry') ?? '';
                    return array_keys(PipelineRoleResolver::getOptions($industry));
                  }),

                TextInput::make('label_key')
                  ->label(__('Label Key (e.g. Start Clip)'))
                  ->required()
                  ->translatable(),

                Select::make('type_code')
                  ->label(__('Target Product Type'))
                  ->options(fn() => ProductType::pluck('name', 'code'))
                  ->required()
                  ->live()
                  ->afterStateUpdated(function ($state, callable $set, Get $get) {
                    if ($state) {
                      $industry = $get('../../../industry') ?? '';
                      $defaultType = PipelineRoleResolver::getDefaultProductType($industry, $state);
                      if ($defaultType && blank($get('type_code'))) {
                        $set('type_code', $defaultType);
                      }
                      if (blank($get('label_key'))) {
                        $set('label_key', PipelineRoleResolver::getLabel($industry, $state));
                      }
                    }
                  }),

                Toggle::make('is_required')
                  ->label(__('Is Required'))
                  ->default(false),

                Toggle::make('is_multiple')
                  ->label(__('Is Multiple (Folder)'))
                  ->default(false),
              ])
              ->columns(5)
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

                    TextInput::make('industry')
                      ->label(__('Industry'))
                      ->required()
                      ->live()
                      ->maxLength(50),

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
