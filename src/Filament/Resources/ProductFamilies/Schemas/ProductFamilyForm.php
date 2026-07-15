<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductFamilies\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Illuminate\Support\Arr;

class ProductFamilyForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('FamilyTabs')
        ->tabs([
          Tabs\Tab::make(__('Family Information'))
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
                        $set('code', \Illuminate\Support\Str::slug($state, '_'));
                        $set('slug', \Illuminate\Support\Str::slug($state, '-'));
                      }
                    })
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),


                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->helperText(__('Used for clean URLs (SEO)')),

                  TextInput::make('icon')
                    ->label(__('Icon'))
                    ->placeholder('heroicon-o-sparkles'),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true),
                ])
                ->columns(2),
            ]),

          Tabs\Tab::make(__('Schema Builder'))
            ->icon('heroicon-o-rectangle-group')
            ->schema([
              Section::make(__('Product Types Schema'))
                ->description(__('Define custom physical parameters (like size steps, max stack) that will be available for all product types within this family.'))
                ->schema([

                  Repeater::make('meta_schema')
                    ->hiddenLabel()
                    ->schema([
                      TextInput::make(SchemaKey::KEY)->label(__('Key (System)'))->required()->alphaDash(),
                      TextInput::make(SchemaKey::LABEL)->label(__('Label (Human readable)'))->required()->translatable(),

                      Select::make(SchemaKey::TYPE)
                        ->label(__('Field Type'))
                        ->options(Arr::only(SchemaFieldType::options(), [
                          SchemaFieldType::TEXT,
                          SchemaFieldType::NUMBER,
                          SchemaFieldType::BOOLEAN,
                        ]))
                        ->required()
                        ->native(false),

                      Select::make(SchemaKey::WIDTH)->label(__('UI Width'))->options([1 => __('Minimum Part'), 2 => __('Full Width')])->default(1),
                    ])
                    ->columns(4)
                    ->addActionLabel(__('Add Field'))
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state[SchemaKey::KEY] ?? null),
                ])
            ]),

          SalesChannelsTab::make('family'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
