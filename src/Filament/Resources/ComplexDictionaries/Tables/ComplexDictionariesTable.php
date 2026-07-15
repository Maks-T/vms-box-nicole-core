<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\FilterHelper;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Models\ComplexDictionary;

class ComplexDictionariesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),

        TableHelper::codeColumn('code'), // Shared

        TextColumn::make('schema_fields_count')
          ->label(__('Fields'))
          ->state(
            fn (ComplexDictionary $record): int => is_array($record->meta_schema)
              ? count($record->meta_schema)
              : 0,
          )
          ->badge()
          ->color('info'),

        TableHelper::statusColumn(),
      ])
      ->filters([
        FilterHelper::activeFilter(),
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc');
  }
}
