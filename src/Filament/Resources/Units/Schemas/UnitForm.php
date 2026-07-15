<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;

class UnitForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('UnitTabs')
        ->tabs([
          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-beaker')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name (Full)'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                      FormHelper::generateSlug('slug', '-', false),
                    )
                    ->translatable(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),

                  TextInput::make('symbol')
                    ->label(__('Symbol'))
                    ->required()
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Standard Code (OKEI / UN CEFACT)'))
                    ->nullable()
                    ->alphaDash(),

                  
                  TextInput::make('external_code')
                    ->label(__('External Code'))
                    ->nullable()
                    ->helperText(__('Used for ERP integration')),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('unit'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
