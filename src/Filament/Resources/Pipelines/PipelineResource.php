<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Pipelines;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\CreatePipeline;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\EditPipeline;
use Nicole\Box\Core\Filament\Resources\Pipelines\Pages\ListPipelines;
use Nicole\Box\Core\Filament\Resources\Pipelines\Schemas\PipelineForm;
use Nicole\Box\Core\Filament\Resources\Pipelines\Tables\PipelinesTable;

class PipelineResource extends Resource
{
  protected static ?string $model = Pipeline::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

  protected static ?string $recordTitleAttribute = 'name';

  protected static ?string $slug = 'pipelines';

  protected static ?int $navigationSort = 4;

  public static function getNavigationGroup(): ?string
  {
    return __('Configurations');
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
