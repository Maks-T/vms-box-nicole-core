<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\CreateComplexDictionary;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\EditComplexDictionary;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\ListComplexDictionaries;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\RelationManagers\RecordsRelationManager;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Schemas\ComplexDictionaryForm;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Tables\ComplexDictionariesTable;
use Nicole\Box\Core\Models\ComplexDictionary;

class ComplexDictionaryResource extends Resource
{
    protected static ?string $model = ComplexDictionary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'complex-dictionaries';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Complex Dictionary');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Complex Dictionaries');
    }

    public static function form(Schema $schema): Schema
    {
        return ComplexDictionaryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplexDictionariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [RecordsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplexDictionaries::route('/'),
            'create' => CreateComplexDictionary::route('/create'),
            'edit' => EditComplexDictionary::route('/{record}/edit'),
        ];
    }
}
