<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Models\Customer;

class OrderForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Grid::make(3)->schema([

        Grid::make(1)
          ->columnSpan(2)
          ->schema([
            Section::make(__('Pricing & Economy'))
              ->schema([
                TextInput::make('grand_total')
                  ->label(__('Total'))
                  ->numeric()
                  ->required()
                  ->disabled()
                  ->prefix(function ($record) {
                    if ($record?->currency) {
                      return $record->currency;
                    }
                    $pricingManager = app(\Nicole\Box\Core\Services\PricingManager::class);
                    return $pricingManager->baseCurrency->symbol_native ?? ($pricingManager->baseCurrency->symbol ?? '₽');
                  }),

                Select::make('status_id')
                  ->label(__('Status'))
                  ->relationship('status', 'name')
                  ->preload()
                  ->searchable()
                  ->native(false)
                  ->required(),
              ])
              ->columns(2),

            Section::make(__('Comments'))
              ->schema([
                Textarea::make('customer_comment')
                  ->label(__('Client Comment'))
                  ->placeholder(__('Client requirements/wishes...'))
                  ->rows(3)
                  ->disabled(),

                Textarea::make('manager_comment')
                  ->label(__('Manager Comment'))
                  ->placeholder(__('Internal processing comments...'))
                  ->rows(3),
              ]),
          ]),

        Grid::make(1)
          ->columnSpan(1)
          ->schema([
            Section::make(__('Identity & Status'))
              ->schema([
                Select::make('customer_id')
                  ->label(__('Customer'))
                  ->relationship('customer', 'phone')
                  ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->full_name} ({$record->phone})")
                  ->searchable()
                  ->preload(),

                Select::make('manager_id')
                  ->label(__('Staff'))
                  ->relationship('manager', 'name')
                  ->searchable()
                  ->preload(),

                TextInput::make('external_code')
                  ->label(__('External Code'))
                  ->placeholder(__('ERP / 1C Code')),
              ]),

          ]),
      ]),
    ]);
  }
}
