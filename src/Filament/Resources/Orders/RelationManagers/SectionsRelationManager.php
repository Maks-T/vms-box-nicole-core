<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Nicole\Box\Core\Filament\Resources\Orders\Schemas\Tabs\EstimateTab;
use Nicole\Box\Core\Models\OrderSection;

use Njxqlus\Filament\Components\Forms\RelationManager as NjxqlusRelationManager;

class SectionsRelationManager extends RelationManager
{
  protected static string $relationship = 'sections';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Order Sections');
  }

  public function form(Schema $schema): Schema
  {
    return $schema->components([
      TextInput::make('title')
        ->label(__('Name'))
        ->required()
        ->columnSpanFull(),
      TextInput::make('price_grand_total')
        ->label(__('Total'))
        ->numeric()
        ->required(),
    ]);
  }

  /**
   * Таблица изделий в заказе
   */
  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('title')
      ->columns([
        ImageColumn::make('drawing')
          ->label(__('Photo'))
          ->state(function (OrderSection $record) {
            return $record->getPreviewUrl() ?? null;
          })
          ->circular()
          ->imageWidth(60)
          ->imageHeight(60),

        TextColumn::make('title')
          ->label(__('Name'))
          ->weight('bold')
          ->searchable(),

        TextColumn::make('description')
          ->label(__('Technical Specifications'))
          ->state(function (OrderSection $record) {
            if (empty($record->description)) {
              return '-';
            }

            return collect($record->description)
              ->map(fn($spec) => "▪ {$spec['name']}: {$spec['description']}")
              ->join("<br />");
          })
          ->wrap()
          ->html()
          ->color('gray')
          ->fontFamily('sans'),

        TextColumn::make('price_grand_total')
          ->label(__('Total'))
          ->money($this->getOwnerRecord()->currency)
          ->weight('bold')
          ->color('primary')
          ->alignEnd(),
      ])
      ->recordActions([

        Action::make('view_estimate')
          ->label(__('Details'))
          ->icon('heroicon-o-document-text')
          ->color('info')
          ->modalHeading(fn(OrderSection $record) => $record->title)
          ->slideOver()
          ->modalWidth(Width::SevenExtraLarge)
          ->modalSubmitAction(false)
          ->modalCancelActionLabel(__('Close'))
          ->fillForm(function (OrderSection $record): array {
            $state = [];
            foreach ($record->description ?? [] as $spec) {
              $cleanKey = str_replace(':', '', $spec['name']);
              $state["spec_" . str_replace(' ', '_', $cleanKey)] = $spec['description'];
            }
            return $state;
          })
          ->schema([
            Tabs::make('SectionDetails')
              ->tabs([

                EstimateTab::make(),

                Tab::make(__('Technical Specifications'))
                  ->icon('heroicon-o-list-bullet')
                  ->schema([
                    KeyValueEntry::make('description')
                      ->label(__('Technical Specifications'))
                      ->state(function (OrderSection $record) {
                        return collect($record->description ?? [])
                          ->pluck('description', 'name')
                          ->toArray();
                      })
                      ->keyLabel(__('Parameter'))
                      ->valueLabel(__('Value'))
                      ->columnSpanFull(),
                  ]),

                Tab::make(__('Drawings'))
                  ->icon('heroicon-o-photo')
                  ->visible(fn (OrderSection $record) => $record->hasMedia('drawing'))
                  ->schema([
                    TextEntry::make('drawing_preview')
                      ->hiddenLabel()
                      ->state(function (OrderSection $record) {
                        $url = $record->getPreviewUrl();
                        if (!$url) return '-';
                        return new HtmlString("
                            <div style='text-align: center; padding: 20px;'>
                                <img src='{$url}' style='max-height: 400px; width: auto; object-fit: contain; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.05);' />
                            </div>
                        ");
                      }),
                  ]),

                Tab::make(__('Catalog Products'))
                  ->icon('heroicon-o-puzzle-piece')
                  ->schema([
                    NjxqlusRelationManager::make()
                      ->manager(\Nicole\Box\Core\Filament\Resources\Orders\RelationManagers\ProductsRelationManager::class)
                      ->lazy(false)
                  ]),
              ])
              ->columnSpanFull()
          ]),

        DeleteAction::make(),
      ])
      ->defaultSort('id', 'asc');
  }

}
