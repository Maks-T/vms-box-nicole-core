<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Filament\Forms\Components\VariantSelect;
use Illuminate\Database\Eloquent\Model;


class VariantsRelationManager extends RelationManager
{
  protected static string $relationship = 'variants';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Catalog Products');
  }

  public function form(Schema $schema): Schema
  {
    return $schema->components([]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('sku')
      ->columns([
        ImageColumn::make('preview_image')
          ->label(__('Photo'))
          ->state(function (ProductVariant $record) {
            return $record->getPreviewUrl() ?? null;
          })
          ->circular()
          ->imageWidth(45)
          ->imageHeight(45),

        TextColumn::make('product.name')
          ->label(__('Parent Product'))
          ->state(fn (ProductVariant $record) => $record->product?->name ?? '-')
          ->weight('bold')
          ->searchable(),

        TextColumn::make('sku')
          ->label('SKU')
          ->fontFamily('mono')
          ->color('gray')
          ->searchable(),

        TextColumn::make('stock')
          ->label(__('Stock'))
          ->numeric()
          ->badge()
          ->state(fn (ProductVariant $record) => $record->stock)
          ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
          ->alignEnd(),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        AssociateAction::make()
          ->label(__('Attach'))
          ->modalHeading(__('Attach Product Variant'))
          ->modalSubmitActionLabel(__('Attach'))
          ->recordSelect(fn () => VariantSelect::make('recordId')->hiddenLabel()->placeholder(__('Select variant'))),
      ])
      ->recordActions([
        DissociateAction::make()
          ->label(__('Detach'))
          ->modalHeading(__('Detach Product Variant'))
          ->modalSubmitActionLabel(__('Detach'))
          ->successNotificationTitle(__('Detached successfully')),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DissociateBulkAction::make()
            ->label(__('Detach selected'))
            ->successNotificationTitle(__('Detached successfully')),
        ]),
      ]);
  }
}
