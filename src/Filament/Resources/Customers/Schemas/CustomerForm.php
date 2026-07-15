<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Grid::make(3)->schema([


        Grid::make(1)
          ->columnSpan(2)
          ->schema([
            Section::make(__('General Information'))
              ->schema([
                TextInput::make('last_name')
                  ->label(__('Last Name'))
                  ->maxLength(100),
                TextInput::make('first_name')
                  ->label(__('First Name'))
                  ->maxLength(100),
                TextInput::make('middle_name')
                  ->label(__('Middle Name'))
                  ->maxLength(100),
              ])
              ->columns(3),

            Section::make(__('Contact Information'))
              ->schema([
                TextInput::make('phone')
                  ->label(__('Phone'))
                  ->tel()
                  ->maxLength(50),
                TextInput::make('email')
                  ->label('Email address')
                  ->email()
                  ->maxLength(150),
                TextInput::make('address')
                  ->label(__('Address'))
                  ->columnSpanFull(),
              ])
              ->columns(2),
          ]),


        Grid::make(1)
          ->columnSpan(1)
          ->schema([
            Section::make(__('Additional Data'))
              ->schema([
                Textarea::make('admin_notes')
                  ->label(__('Admin Notes'))
                  ->rows(5)
                  ->placeholder(__('Write manager comments about this client...')),

                TextInput::make('last_ip')
                  ->label(__('IP Address'))
                  ->disabled()
                  ->dehydrated(false)
                  ->placeholder('-'),
              ]),
          ]),
      ]),
    ]);
  }
}
