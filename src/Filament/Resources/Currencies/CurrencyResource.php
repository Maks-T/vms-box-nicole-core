<?php

namespace Nicole\Box\Core\Filament\Resources\Currencies;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\CreateCurrency;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\EditCurrency;
use Nicole\Box\Core\Filament\Resources\Currencies\Pages\ListCurrencies;
use Nicole\Box\Core\Filament\Resources\Currencies\Schemas\CurrencyForm;
use Nicole\Box\Core\Filament\Resources\Currencies\Tables\CurrenciesTable;
use Nicole\Box\Core\Models\Currency;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'сurrencies';

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Currency');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Currencies');
    }

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
