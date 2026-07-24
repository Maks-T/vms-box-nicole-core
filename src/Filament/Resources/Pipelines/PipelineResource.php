<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Pipelines;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Clusters\Pipelines\PipelineCluster;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\CreatePipeline;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\EditPipeline;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\ListPipelines;
use Nicole\Box\Core\Filament\Resources\Pipelines\Schemas\PipelineForm;
use Nicole\Box\Core\Filament\Resources\Pipelines\Tables\PipelinesTable;
use Nicole\Box\Core\Models\Pipeline;

class PipelineResource extends Resource
{
    protected static ?string $model = Pipeline::class;

    // Привязка к общему Кластеру для верхних вкладок
    protected static ?string $cluster = PipelineCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'pipelines';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Pipeline Schemas');
    }

    public static function getModelLabel(): string
    {
        return __('Pipeline Schema');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pipeline Schemas');
    }

    public static function form(Schema $schema): Schema
    {
        return PipelineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PipelinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPipelines::route('/'),
            'create' => CreatePipeline::route('/create'),
            'edit' => EditPipeline::route('/{record}/edit'),
        ];
    }
}
