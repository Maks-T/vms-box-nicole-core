<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Nicole\Box\Core\Filament\Helpers\TableHelper;
use Nicole\Box\Core\Filament\Resources\Products\Filters\ProductFilters;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Services\Catalog\ReplicationService;
use Nicole\Box\Core\Services\PricingManager;

class ProductsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TableHelper::idColumn(),

        TableHelper::photoColumn(), // Shared

        TextColumn::make('name')
          ->label(__('Name'))
          ->searchable(['name', 'slug', 'external_code'])
          ->sortable()
          ->toggleable()
          ->wrap(),

        TableHelper::externalCodeColumn(),

        TableHelper::codeColumn('code')
          ->label(__('Code') . ' / Артикул'),

        TableHelper::codeColumn('slug')
          ->label('Символьный код (Slug)'),

        TextColumn::make('category.name')
          ->label(__('Category'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true)
          ->sortable(),

        TextColumn::make('type.name')
          ->label(__('Product Type'))
          ->badge()
          ->toggleable(),

        TextColumn::make('catalog_type')
          ->label('Тип позиции')
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('unit.name')
          ->label(__('Unit'))
          ->badge()
          ->color('gray')
          ->toggleable(isToggledHiddenByDefault: true)
          ->sortable(),

        TextColumn::make('short_description')
          ->label(__('Short Description'))
          ->limit(30)
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('description')
          ->label(__('Description'))
          ->limit(30)
          ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('min_price')
          ->label(__('Price From'))
          ->money(fn() => app(PricingManager::class)->baseCurrency->code)
          ->sortable()
          ->toggleable(),

        TableHelper::statusColumn(), // Активность (Toggle)

        TextColumn::make('variants_count')
          ->label(__('SKUs'))
          ->counts('variants')
          ->badge()
          ->color('info')
          ->toggleable(),

        TableHelper::sortOrderColumn(),
        TableHelper::createdAtColumn(),
        TableHelper::updatedAtColumn(),
      ])
      ->columnManagerColumns(2)
      ->filtersLayout(FiltersLayout::AboveContent)
      ->filtersFormColumns(3)
      ->filters(ProductFilters::all())
      ->recordActions([
        ActionGroup::make([
          EditAction::make(),
          ReplicateAction::make()
            ->label(__('Replicate'))
            ->modalHeading(__('Replicate Product'))
            ->schema([
              TextInput::make('slug')
                ->label(__('Slug'))
                ->required()
                ->default(fn (Product $record, ReplicationService $service): string => $service->generateUniqueProductSlug((string) $record->slug))
                ->maxLength(255),
              TextInput::make('name')
                ->label(__('Product Name'))
                ->placeholder(fn (Product $record): ?string => $record->getTranslation('name', app()->getLocale(), false)),
              Toggle::make('with_variants')
                ->label(__('Duplicate with variants (SKUs)'))
                ->helperText(__('If enabled, all variations will be cloned with their prices and attributes'))
                ->default(true),
            ])
            ->action(function (Product $record, array $data, ReplicationService $service): void {
              $overrides = ['slug' => $data['slug']];
              if (!empty($data['name'])) {
                $overrides['name'] = [app()->getLocale() => $data['name']];
              }
              $withVariants = (bool) ($data['with_variants'] ?? true);
              $replica = $service->replicateProduct($record, $withVariants, $overrides);

              Notification::make()
                ->success()
                ->title(__('Product duplicated: :name', ['name' => $replica->name]))
                ->send();
            }),
          DeleteAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          BulkAction::make('replicate')
            ->label(__('Replicate'))
            ->icon('heroicon-o-document-duplicate')
            ->requiresConfirmation()
            ->modalHeading(__('Replicate selected products'))
            ->action(function (Collection $records, ReplicationService $service): void {
              $count = 0;
              foreach ($records as $record) {
                if ($record instanceof Product) {
                  $service->replicateProduct($record, true);
                  $count++;
                }
              }
              Notification::make()
                ->success()
                ->title(__('Successfully duplicated :count products', ['count' => $count]))
                ->send();
            })
            ->deselectRecordsAfterCompletion(),
          DeleteBulkAction::make(),
        ]),
      ])
      ->persistFiltersInSession()
      ->persistSearchInSession()
      ->persistSortInSession();
  }
}
