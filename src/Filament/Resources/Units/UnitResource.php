<?php

namespace Nicole\Box\Core\Filament\Resources\Units;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Units\Pages\CreateUnit;
use Nicole\Box\Core\Filament\Resources\Units\Pages\EditUnit;
use Nicole\Box\Core\Filament\Resources\Units\Pages\ListUnits;
use Nicole\Box\Core\Filament\Resources\Units\Schemas\UnitForm;
use Nicole\Box\Core\Filament\Resources\Units\Tables\UnitsTable;
use Nicole\Box\Core\Models\Unit;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'units';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Units');
    }

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
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
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
