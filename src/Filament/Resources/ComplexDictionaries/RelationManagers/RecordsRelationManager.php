<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ComplexDictionaries\RelationManagers;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Support\Constants\SchemaKey;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;

class RecordsRelationManager extends RelationManager
{
  protected static string $relationship = 'records';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Dictionary Records');
  }

  protected function getProcessedFields(): array
  {
    $ownerRecord = $this->getOwnerRecord();
    $schemaFields = $ownerRecord->meta_schema ?? [];
    $processed = [];

    foreach ($schemaFields as $field) {
      $key = $field[SchemaKey::KEY];
      $label = is_array($field[SchemaKey::LABEL])
        ? $field[SchemaKey::LABEL][app()->getLocale()] ?? (collect($field[SchemaKey::LABEL])->first() ?? $key)
        : $field[SchemaKey::LABEL];

      $processed[] = [
        SchemaKey::KEY => $key,
        SchemaKey::TYPE => $field[SchemaKey::TYPE],
        SchemaKey::LABEL => $label,
        'payloadKey' => "meta.{$key}",
      ];
    }

    return $processed;
  }

  public function form(Schema $schema): Schema
  {
    $dynamicComponents = [];

    foreach ($this->getProcessedFields() as $field) {
      $input = match ($field[SchemaKey::TYPE]) {
        SchemaFieldType::BOOLEAN => Toggle::make($field['payloadKey'])->inline(false),
        SchemaFieldType::NUMBER => TextInput::make($field['payloadKey'])->numeric(),
        default => TextInput::make($field['payloadKey']),
      };
      $dynamicComponents[] = $input->label((string) $field[SchemaKey::LABEL]);
    }

    return $schema->components([
      Tabs::make('RecordModalTabs')
        ->tabs([
          Tab::make(__('Data'))
            ->icon('heroicon-o-document-text')
            ->schema([
              Section::make(__('Record Identity'))
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(FormHelper::generateSlug('slug', '-', false))
                    ->translatable(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->alphaDash(),

                  TextInput::make('external_code')
                    ->label(__('External Code'))
                    ->nullable(),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true),
                ])
                ->columns(2),

              Section::make(__('Additional Data'))->schema($dynamicComponents),
            ]),
          SalesChannelsTab::make('complex_dictionary_record'),
        ])
        ->columnSpanFull(),
    ]);
  }

  public function table(Table $table): Table
  {
    $columns = [
      TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
    ];

    foreach ($this->getProcessedFields() as $field) {
      if ($field[SchemaKey::TYPE] === SchemaFieldType::BOOLEAN) {
        $columns[] = IconColumn::make($field['payloadKey'])
          ->label($field[SchemaKey::LABEL])
          ->boolean()
          ->toggleable();
      } elseif ($field[SchemaKey::TYPE] === SchemaFieldType::NUMBER) {
        $columns[] = TextColumn::make($field['payloadKey'])
          ->label($field[SchemaKey::LABEL])
          ->numeric()
          ->sortable()
          ->toggleable();
      } else {
        $columns[] = TextColumn::make($field['payloadKey'])
          ->label($field[SchemaKey::LABEL])
          ->searchable()
          ->toggleable();
      }
    }

    $columns[] = IconColumn::make('is_active')->label(__('Is Active'))->boolean();
    $columns[] = TextColumn::make('sort_order')->label(__('Sort'))->numeric()->sortable();

    return $table
      ->recordTitleAttribute('name')
      ->columns($columns)
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->headerActions([CreateAction::make()->modalWidth(Width::SevenExtraLarge)])
      ->recordActions([EditAction::make()->modalWidth(Width::SevenExtraLarge), DeleteAction::make()])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          BulkAction::make('activate')
            ->label(__('Activate'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
          BulkAction::make('deactivate')
            ->label(__('Deactivate'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
        ]),
      ]);
  }
}
