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

                  // ДОБАВИТЬ ЭТО ПОЛЕ:
                  Select::make('option_param_type')
                    ->label(__('Option Parameter Type'))
                    ->options([
                      'string' => __('String'),
                      'numeric' => __('Numeric'),
                      'boolean' => __('Boolean (Toggle)'),
                    ])
                    ->nullable()
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get) => $get('type') === Attribute::TYPE_DICTIONARY),

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

          Tabs\Tab::make(__('Admin Display & Grid'))
            ->icon('heroicon-o-table-cells')
            ->schema([
              Section::make(__('Table & Grid Representation'))
                ->relationship('uiSetting')
                ->schema([
                  Toggle::make('show_in_variant_grid')
                    ->label(__('Show in Variant Grid'))
                    ->default(false),

                  Toggle::make('show_in_product_grid')
                    ->label(__('Show in Product Grid'))
                    ->default(false),

                  Select::make('grid_component_type')
                    ->label(__('Display Component Type'))
                    ->options([
                      'auto' => __('Auto'),
                      'color' => __('Color Swatch'),
                      'badge' => __('Badge'),
                      'text' => __('Plain Text'),
                      'boolean' => __('Boolean Icon'),
                    ])
                    ->default('auto')
                    ->native(false),

                  TextInput::make('grid_sort_order')
                    ->label(__('Column Sort Order'))
                    ->numeric()
                    ->default(0),

                  Toggle::make('is_inline_editable')
                    ->label(__('Inline Editable in Grid'))
                    ->default(false),

                  Toggle::make('is_filterable')
                    ->label(__('Filterable in Admin'))
                    ->default(false),

                  Toggle::make('is_searchable')
                    ->label(__('Searchable in Admin'))
                    ->default(false),

                  Select::make('view_roles')
                    ->label(__('Roles Allowed to View'))
                    ->options(fn () => config('nicole.models.role', \Nicole\Box\Core\Models\Role::class)::pluck('name', 'name'))
                    ->multiple()
                    ->preload()
                    ->searchable(),

                  Select::make('edit_roles')
                    ->label(__('Roles Allowed to Edit Inline'))
                    ->options(fn () => config('nicole.models.role', \Nicole\Box\Core\Models\Role::class)::pluck('name', 'name'))
                    ->multiple()
                    ->preload()
                    ->searchable(),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('attribute'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
