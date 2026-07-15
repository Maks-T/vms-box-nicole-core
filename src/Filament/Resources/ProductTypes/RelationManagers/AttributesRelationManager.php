<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Assigned Attributes');
    }

    protected function getPivotFields(): array
    {
        return [
            Toggle::make('is_required')->label(__('Required'))->default(false),

            Toggle::make('is_variant_only')
                ->label(__('Variant Only'))
                ->helperText(__('Only asked for SKU, not the base product.'))
                ->default(false),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(),

                TextColumn::make('code')
                    ->label(__('Code'))
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('type')->label(__('Type'))->badge(),

                ToggleColumn::make('is_required')->label(__('Required')),

                ToggleColumn::make('is_variant_only')->label(__('Variant Only')),
            ])
            ->reorderable('sort_order')
            ->defaultSort('attribute_product_type.sort_order', 'asc')
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(
                        fn (AttachAction $action): array => array_merge(
                            [$action->getRecordSelect()],
                            $this->getPivotFields(),
                        ),
                    ),
            ])
            ->recordActions([
            EditAction::make()->schema($this->getPivotFields()),
            DetachAction::make(),
        ])
            ->toolbarActions([BulkActionGroup::make([DetachBulkAction::make()])]);
    }
}
