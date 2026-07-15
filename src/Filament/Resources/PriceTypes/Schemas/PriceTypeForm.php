<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\PriceTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Models\PriceType;

class PriceTypeForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('PriceTypeTabs')
        ->tabs([

          Tabs\Tab::make(__('Price Category Details'))
            ->icon('heroicon-o-banknotes')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
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


                  TextInput::make('external_code')
                    ->label(__('External Code'))
                    ->nullable()
                    ->helperText(__('Used for ERP integration')),

                  TextInput::make('description')
                    ->label(__('Description'))
                    ->columnSpanFull()
                    ->translatable(),

                  Select::make('currency_id')
                    ->label(__('Currency'))
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->preload()
                    ->default(1)
                    ->columnSpanFull(),

                  ProtectDefaultRecord::formToggle(PriceType::class, 'Default Price Type')
                    ->helperText(
                      __('Used for main calculations (e.g. retail price on website)'),
                    )
                    ->columnSpanFull(),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('price_type'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
