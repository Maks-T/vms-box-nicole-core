<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\Unit;

class AttributeForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('AttributeTabs')
        ->tabs([
          Tabs\Tab::make(__('General Identity'))
            ->icon('heroicon-o-identification')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(FormHelper::generateSlug('code', '_'))
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),

                  Select::make('type')
                    ->label(__('Type'))
                    ->required()
                    ->options([
                      Attribute::TYPE_STRING => __('String'),
                      Attribute::TYPE_NUMERIC => __('Numeric'),
                      Attribute::TYPE_BOOLEAN => __('Boolean (Toggle)'),
                      Attribute::TYPE_DICTIONARY => __('Dictionary (Select)'),
                      Attribute::TYPE_COMPLEX => __('Complex Dictionary'),
                    ])
                    ->native(false)
                    ->live(),

                  Select::make('complex_dictionary_id')
                    ->label(__('Complex Dictionary'))
                    ->relationship('complexDictionary', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn(Get $get) => $get('type') === Attribute::TYPE_COMPLEX)
                    ->required(fn(Get $get) => $get('type') === Attribute::TYPE_COMPLEX),

                  Select::make('unit_id')
                    ->label(__('Unit'))
                    ->options(fn() => Unit::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->visible(fn(Get $get) => $get('type') === Attribute::TYPE_NUMERIC),

                  
                  Toggle::make('is_multiple')
                    ->label(__('Multiple choice'))
                    ->default(false)
                    ->visible(fn(Get $get) => in_array($get('type'), [Attribute::TYPE_DICTIONARY, Attribute::TYPE_COMPLEX])),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('attribute'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
