<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Filament\Resources\ProductVariants\ProductVariantResource;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use Nicole\Box\Core\Filament\Resources\ProductVariants\Tables\ProductVariantsTable;
use Nicole\Box\Core\Filament\Concerns\HasDynamicEavFields;

class VariantsRelationManager extends RelationManager
{

  use HasDynamicEavFields;

  protected static string $relationship = 'variants';

  public static function getTitle(Model $ownerRecord, string $pageClass): string
  {
    return __('Product Variants');
  }

  public function form(Schema $schema): Schema
  {
    return ProductVariantForm::configure($schema);
  }

  public function table(Table $table): Table
  {
    return ProductVariantsTable::configure($table)

      ->recordActions([
        EditAction::make()
          ->slideOver()
          ->modalWidth(Width::SevenExtraLarge)

          ->mutateRecordDataUsing(function (Model $record, array $data): array {
            $this->loadEavData($record, $data);
            return $data;
          })

          ->after(function (Model $record, array $data) {
            $this->saveEavData($record, $data['eav'] ?? []);
          }),

        Action::make('go_to_variant')
          ->label(__('Full Edit'))
          ->icon('heroicon-o-arrow-top-right-on-square')
          ->url(
            fn (Model $record): string => ProductVariantResource::getUrl(
              'edit',
              ['record' => $record],
            ),
          )
          ->openUrlInNewTab(),
      ])
      ->headerActions([
        CreateAction::make()
          ->slideOver()
          ->modalWidth(Width::SevenExtraLarge)
          ->after(function (Model $record, array $data) {
            $this->saveEavData($record, $data['eav'] ?? []);
          }),
      ]);
  }
}
