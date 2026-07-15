<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Staff\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Account Information'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label(__('Password'))
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                ])
                ->columns(2),

            Section::make(__('Access Control'))->schema([
                Select::make('roles')
                    ->label(__('Roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]),
        ]);
    }
}
