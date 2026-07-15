<?php

namespace Nicole\Box\Core\Filament\Resources\PriceGroups;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\PriceGroups\Pages\CreatePriceGroup;
use Nicole\Box\Core\Filament\Resources\PriceGroups\Pages\EditPriceGroup;
use Nicole\Box\Core\Filament\Resources\PriceGroups\Pages\ListPriceGroups;
use Nicole\Box\Core\Filament\Resources\PriceGroups\Schemas\PriceGroupForm;
use Nicole\Box\Core\Filament\Resources\PriceGroups\Tables\PriceGroupsTable;
use Nicole\Box\Core\Filament\Resources\PriceGroups\RelationManagers\VariantsRelationManager;
use Nicole\Box\Core\Models\PriceGroup;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class PriceGroupResource extends Resource
{
  use Translatable;

  protected static ?string $model = PriceGroup::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

  protected static ?string $recordTitleAttribute = 'name';

  protected static ?string $slug = 'price-groups';

  protected static ?int $navigationSort = 6;

  public static function getNavigationGroup(): ?string
  {
    return __('Catalog Settings');
  }

  public static function getModelLabel(): string
  {
    return __('Price Group');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Price Groups');
  }

  public static function form(Schema $schema): Schema
  {
    return PriceGroupForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return PriceGroupsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      VariantsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListPriceGroups::route('/'),
      'create' => CreatePriceGroup::route('/create'),
      'edit' => EditPriceGroup::route('/{record}/edit'),
    ];
  }
}
