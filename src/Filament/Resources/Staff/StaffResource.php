<?php

namespace Nicole\Box\Core\Filament\Resources\Staff;

use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\CreateStaff;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\EditStaff;
use Nicole\Box\Core\Filament\Resources\Staff\Pages\ListStaff;
use Nicole\Box\Core\Filament\Resources\Staff\Schemas\StaffForm;
use Nicole\Box\Core\Filament\Resources\Staff\Tables\StaffTable;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'staff';

    public static function getNavigationGroup(): ?string
    {
        return __('Access Control');
    }

    public static function getModelLabel(): string
    {
        return __('Staff Member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Staff');
    }

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
