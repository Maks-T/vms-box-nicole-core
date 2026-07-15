<?php

namespace Nicole\Box\Core\Filament\Resources\PriceTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\CreatePriceType;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\EditPriceType;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Pages\ListPriceTypes;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Schemas\PriceTypeForm;
use Nicole\Box\Core\Filament\Resources\PriceTypes\Tables\PriceTypesTable;
use Nicole\Box\Core\Models\PriceType;

class PriceTypeResource extends Resource
{
    protected static ?string $model = PriceType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'price-types';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Price Type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Price Types');
    }

    public static function form(Schema $schema): Schema
    {
        return PriceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceTypes::route('/'),
            'create' => CreatePriceType::route('/create'),
            'edit' => EditPriceType::route('/{record}/edit'),
        ];
    }
}
