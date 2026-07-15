<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SchemaKey;

class ComplexDictionaryForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('DictionaryTabs')
        ->tabs([

          Tabs\Tab::make(__('Dictionary Identity'))
            ->icon('heroicon-o-identification')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                      FormHelper::generateSlug('code', '_', false),
                    )
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->columnSpanFull(),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('complex_dictionary'),

          Tabs\Tab::make(__('Schema Builder'))
            ->icon('heroicon-o-rectangle-group')
            ->schema([
              Section::make(__('Dictionary Fields Schema'))
                ->description(__('Define the dynamic fields that this dictionary will store (e.g. min_size, material_density).'))
                ->schema([

                  Repeater::make('meta_schema')
                    ->hiddenLabel()
                    ->schema([

                      TextInput::make(SchemaKey::KEY)
                        ->label(__('Key (System)'))
                        ->placeholder('material_density')
                        ->required()
                        ->alphaDash(),

                      Select::make(SchemaKey::TYPE)
                        ->label(__('Field Type'))
                        ->options(Arr::only(SchemaFieldType::options(), [
                          SchemaFieldType::TEXT,
                          SchemaFieldType::NUMBER,
                          SchemaFieldType::BOOLEAN,
                        ]))
                        ->required()
                        ->live()
                        ->native(false),

                      TextInput::make(SchemaKey::LABEL)
                        ->label(__('Label (Human readable)'))
                        ->required()
                        ->translatable(),

                      Toggle::make(SchemaKey::IS_PUBLIC)
                        ->label(__('Public API Field'))
                        ->helperText(__('Master switch for this field visibility'))
                        ->default(true),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->collapsible()
                    ->addActionLabel(__('Add Field'))
                    ->itemLabel(fn(array $state): ?string => $state[SchemaKey::KEY] ?? null),
                ]),
            ]),
        ])
        ->columnSpanFull(),
    ]);
  }

}
