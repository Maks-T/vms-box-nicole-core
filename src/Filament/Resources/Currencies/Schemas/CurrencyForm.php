<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Models\Currency;

class CurrencyForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('CurrencyTabs')
        ->tabs([
          Tabs\Tab::make(__('General Identity'))
            ->icon('heroicon-o-currency-dollar')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->maxLength(3)
                    ->placeholder('USD'),

                  TextInput::make('external_code')
                    ->label(__('External Code'))
                    ->nullable()
                    ->helperText(__('Used for ERP integration')),

                  TextInput::make('symbol')
                    ->label(__('Symbol'))
                    ->required()
                    ->placeholder('$'),

                  TextInput::make('symbol_native')
                    ->label(__('Official Symbol'))
                    ->placeholder('руб.')
                    ->helperText(__('Used in printed forms and estimates'))
                    ->translatable(),

                  TextInput::make('rate')
                    ->label(__('Rate'))
                    ->numeric()
                    ->step(0.0001)
                    ->required()
                    ->helperText(__('Exchange rate relative to the base currency')),

                  ProtectDefaultRecord::formToggle(Currency::class, 'Base Currency')
                    ->helperText(__('Sets this currency as base (rate will be forced to 1.0)')),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('currency'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
