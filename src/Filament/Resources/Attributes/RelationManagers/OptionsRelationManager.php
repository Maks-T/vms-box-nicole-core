<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Attributes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Models\Attribute;

class OptionsRelationManager extends RelationManager
{
  protected static string $relationship = 'options';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Dictionary Options');
  }

  public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
  {
    return $ownerRecord->type === Attribute::TYPE_DICTIONARY;
  }

  public function form(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('OptionTabs')
        ->tabs([
          Tabs\Tab::make(__('General'))
            ->icon('heroicon-o-identification')
            ->schema([
              Grid::make(2)->schema([
                TextInput::make('value')
                  ->label(__('Value'))
                  ->required()
                  ->live(onBlur: true)
                  ->afterStateUpdated(FormHelper::generateSlug('slug', '-', false))
                  ->translatable(),

                TextInput::make('slug')
                  ->label(__('Slug'))
                  ->required()
                  ->alphaDash(),
              ]),

              Grid::make(2)->schema([
                TextInput::make('external_code')
                  ->label(__('External Code'))
                  ->nullable()
                  ->helperText(__('Used for widget API mapping')),

                
                TextInput::make('meta.extra_value')
                  ->label(__('Extra Value'))
                  ->numeric()
                  ->nullable()
                  ->helperText(__('Formula multiplier (e.g. 240 for 10 days)')),
              ]),
            ]),

          Tabs\Tab::make(__('Visual Representation'))
            ->icon('heroicon-o-paint-brush')
            ->schema([
              Section::make()
                ->description(__('Define color or texture for this option'))
                ->schema([
                  
                  ColorPicker::make('meta.hex')
                    ->label(__('HEX Color'))
                    ->columnSpan(1),

                  SpatieMediaLibraryFileUpload::make('main')
                    ->collection('main')
                    ->label(__('Texture / Icon / Photo'))
                    ->image()
                    ->imageEditor()
                    ->columnSpan(1)
                    ->helperText(__('Uploaded image takes precedence over HEX color')),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('attribute_option'),
        ])
        ->columnSpanFull(),
    ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('slug')
      ->columns([
        SpatieMediaLibraryImageColumn::make('main')
          ->collection('main')
          ->label(__('Photo'))
          ->circular(),

        
        ColorColumn::make('meta.hex')
          ->label(__('Color'))
          ->copyable(),

        TextColumn::make('value')
          ->label(__('Value'))
          ->searchable()
          ->sortable()
          ->weight('bold'),

        TextColumn::make('slug')
          ->label(__('Slug'))
          ->fontFamily('mono')
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('external_code')
          ->label(__('External Code'))
          ->fontFamily('mono')
          ->badge()
          ->color('info')
          ->toggleable(),

        
        TextColumn::make('meta.extra_value')
          ->label(__('Extra Value'))
          ->numeric()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->reorderable('sort_order')
      ->defaultSort('sort_order', 'asc')
      ->headerActions([
        CreateAction::make()->modalWidth(Width::SevenExtraLarge),
      ])
      ->recordActions([
        EditAction::make()->modalWidth(Width::SevenExtraLarge),
        DeleteAction::make(),
      ]);
  }
}
