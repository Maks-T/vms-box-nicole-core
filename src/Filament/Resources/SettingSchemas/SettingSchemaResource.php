<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\SettingSchemas;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\Schemas\SettingSchemaForm;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\Tables\SettingSchemasTable;
use Nicole\Box\Core\Models\SettingSchema;

class SettingSchemaResource extends Resource
{
  protected static ?string $model = SettingSchema::class;

  protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-adjustments-vertical';

  protected static ?string $slug = 'settings-schemas';

  protected static ?int $navigationSort = 10;

  public static function getNavigationGroup(): ?string
  {
    return __('Catalog Settings');
  }

  public static function getModelLabel(): string
  {
    return __('Channel Settings Schema');
  }

  public static function getPluralModelLabel(): string
  {
    return __('Channel Settings Schemas');
  }

  public static function form(Schema $schema): Schema
  {
    return SettingSchemaForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return SettingSchemasTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListSettingSchemas::route('/'),
      'create' => Pages\CreateSettingSchema::route('/create'),
      'edit' => Pages\EditSettingSchema::route('/{record}/edit'),
    ];
  }
}
