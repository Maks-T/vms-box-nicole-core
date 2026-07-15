<?php

namespace Nicole\Box\Core\Filament\Resources\Attributes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\CreateAttribute;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\EditAttribute;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\ListAttributes;
use Nicole\Box\Core\Filament\Resources\Attributes\RelationManagers\OptionsRelationManager;
use Nicole\Box\Core\Filament\Resources\Attributes\Schemas\AttributeForm;
use Nicole\Box\Core\Filament\Resources\Attributes\Tables\AttributesTable;
use Nicole\Box\Core\Models\Attribute;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'attributes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Attribute');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Attributes');
    }

    public static function form(Schema $schema): Schema
    {
        return AttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [OptionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }
}
