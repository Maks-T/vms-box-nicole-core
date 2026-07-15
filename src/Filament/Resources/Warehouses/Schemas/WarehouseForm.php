<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs; 
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper; 
use Nicole\Box\Core\Models\Warehouse;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('WarehouseTabs')
                ->tabs([
                    
                    Tabs\Tab::make(__('Warehouse Information'))
                        ->icon('heroicon-o-home-modern')
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
                                        ->unique(Warehouse::class, 'slug', ignoreRecord: true)
                                        ->alphaDash(),

                                    TextInput::make('phone')->label(__('Contact Phone'))->tel(),

                                    TextInput::make('email')->label(__('Contact Email'))->email(),

                                    TextInput::make('address')
                                        ->label(__('Physical Address'))
                                        ->columnSpanFull(),

                                    Textarea::make('description')
                                        ->label(__('Description'))
                                        ->columnSpanFull()
                                        ->translatable(),

                                    TextInput::make('latitude')->label(__('Latitude'))->numeric(),

                                    TextInput::make('longitude')
                                        ->label(__('Longitude'))
                                        ->numeric(),

                                    KeyValue::make('schedule')
                                        ->label(__('Working Hours'))
                                        ->keyLabel(__('Day'))
                                        ->valueLabel(__('Hours'))
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    
                    SalesChannelsTab::make('warehouse'),
                ])
                ->columnSpanFull(),
        ]);
    }
}
