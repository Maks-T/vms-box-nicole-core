<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Products\Filters;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Services\PricingManager;

class ProductFilters
{
  public static function all(): array
  {
    $pricingManager = app(PricingManager::class);
    $currencySymbol = $pricingManager->baseCurrency->symbol_native ?? ($pricingManager->baseCurrency->symbol ?? '₽');


    return [

      Filter::make('category_id')
        ->label(__('Category'))
        ->columnSpanFull()
        ->schema([
          SelectTree::make('category_id')
            ->label('Дерево разделов каталога')
            ->query(fn() => Category::query(), 'name', 'parent_id')
            ->enableBranchNode()
            ->independent(false)
            ->searchable()
            ->multiple()
            ->placeholder('Начните вводить название категории...'),
        ])
        ->query(function (Builder $query, array $data): Builder {
          if (blank($data['category_id'])) {
            return $query;
          }

          $selectedIds = \Illuminate\Support\Arr::wrap($data['category_id']);
          $allTargetIds = collect($selectedIds)
            ->flatMap(fn($id) => Category::descendantsAndSelf($id)->pluck('id'))
            ->unique();

          return $query->whereIn('category_id', $allTargetIds);
        }),

      Filter::make('advanced_filters')
        ->label(__('Advanced Filters'))
        ->columnSpanFull()
        ->indicateUsing(function (array $data): array {
          $indicators = [];

          if (!empty($data['family_id'])) {
            $count = count($data['family_id']);
            $indicators[] = Indicator::make("Семейства ({$count})")
              ->removeField('family_id');
          }

          if (!empty($data['product_type_id'])) {
            $count = count($data['product_type_id']);
            $indicators[] = Indicator::make("Типы товаров ({$count})")
              ->removeField('product_type_id');
          }

          if (isset($data['is_active']) && $data['is_active'] !== '') {
            $label = $data['is_active'] === '1' ? 'Только активные' : 'Только скрытые';
            $indicators[] = Indicator::make($label)
              ->removeField('is_active');
          }

          if (isset($data['has_images']) && $data['has_images'] !== '') {
            $label = $data['has_images'] === '1' ? 'С изображениями' : 'Без изображений';
            $indicators[] = Indicator::make($label)
              ->removeField('has_images');
          }

          if (filled($data['price_from']) || filled($data['price_to'])) {
            $from = $data['price_from'] ?? '0';
            $to = $data['price_to'] ?? '∞';
            $indicators[] = Indicator::make("Цена: {$from} - {$to}")
              ->removeField('price_from')
              ->removeField('price_to');
          }

          if (filled($data['updated_from']) || filled($data['updated_to'])) {
            $indicators[] = Indicator::make('По дате обновления')
              ->removeField('updated_from')
              ->removeField('updated_to');
          }

          return $indicators;
        })
        ->schema([
          Section::make('Дополнительные параметры поиска')
            ->description(function (Get $get) {
              $active = [];
              if (!empty($get('family_id'))) $active[] = 'семейство';
              if (!empty($get('product_type_id'))) $active[] = 'тип товара';
              if ($get('is_active') !== null && $get('is_active') !== '') $active[] = 'статус на сайте';
              if ($get('has_images') !== null && $get('has_images') !== '') $active[] = 'изображения';
              if ($get('price_from') || $get('price_to')) $active[] = 'цена';
              if ($get('updated_from') || $get('updated_to')) $active[] = 'дата обновления';

              if (empty($active)) {
                return 'Настройте фильтрацию по ценам, остаткам и статусам';
              }

              $badges = collect($active)->map(function ($text) {
                return '<span class="inline-flex items-center gap-x-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-400 border border-primary-600/10 dark:border-primary-500/10">' . e($text) . '</span>';
              })->join(' ');

              return new HtmlString('
                <div class="flex items-center gap-2 flex-wrap mt-1">
                  <span class="text-xs text-gray-500 dark:text-gray-400">Активные фильтры:</span>
                  ' . $badges . '
                </div>
              ');
            })
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsible()
            ->collapsed()
            ->compact()
            ->columns(3)
            ->schema([
              Select::make('family_id')
                ->label(__('Product Family'))
                ->options(fn() => ProductFamily::pluck('name', 'id')->toArray())
                ->multiple()
                ->searchable()
                ->live()
                ->native(false),

              Select::make('product_type_id')
                ->label(__('Product Type'))
                ->options(fn() => ProductType::pluck('name', 'id')->toArray())
                ->multiple()
                ->searchable()
                ->preload()
                ->live()
                ->native(false),

              Select::make('is_active')
                ->label('Статус на сайте')
                ->options([
                  '1' => 'Активен',
                  '0' => 'Скрыт',
                ])
                ->live()
                ->native(false),

              Select::make('has_images')
                ->label('Наличие изображений')
                ->options([
                  '1' => 'Только с фото',
                  '0' => 'Без фотографий',
                ])
                ->live()
                ->native(false),

              Grid::make(2)
                ->schema([
                  TextInput::make('price_from')
                    ->label(__('Price From') . ' (' . $currencySymbol . ')')
                    ->numeric()
                    ->live(onBlur: true)
                    ->placeholder('0'),
                  TextInput::make('price_to')
                    ->label(__('Price To') . ' (' . $currencySymbol . ')')
                    ->numeric()
                    ->live(onBlur: true)
                    ->placeholder('999...'),
                ])
                ->columnSpan(1),

              Grid::make(2)
                ->schema([
                  DatePicker::make('updated_from')
                    ->label('Обновлен с')
                    ->live()
                    ->native(false),
                  DatePicker::make('updated_to')
                    ->label('Обновлен по')
                    ->live()
                    ->native(false),
                ])
                ->columnSpan(1),
            ])
        ])
        ->query(function (Builder $query, array $data) use ($currencySymbol): Builder {
          return $query
            ->when(
              !empty($data['family_id']),
              fn($q) => $q->whereHas('type.family', fn($f) => $f->whereIn('id', (array)$data['family_id']))
            )
            ->when(
              !empty($data['product_type_id']),
              fn($q) => $q->whereIn('product_type_id', (array)$data['product_type_id'])
            )
            ->when(
              isset($data['is_active']) && $data['is_active'] !== '',
              fn($q) => $q->where('is_active', (bool)$data['is_active'])
            )
            ->when(
              $data['has_images'] === '1',
              fn($q) => $q->where(function ($sub) {
                $sub->whereHas('media')
                  ->orWhereHas('variants', fn($vQ) => $vQ->where('is_active', true)->whereHas('media'));
              })
            )
            ->when(
              $data['has_images'] === '0',
              fn($q) => $q->whereDoesntHave('media')
                ->whereDoesntHave('variants', fn($vQ) => $vQ->where('is_active', true)->whereHas('media'))
            )
            ->when(
              filled($data['price_from']),
              fn($q) => $q->where('min_price', '>=', $data['price_from'])
            )
            ->when(
              filled($data['price_to']),
              fn($q) => $q->where('min_price', '<=', $data['price_to'])
            )
            ->when(
              $data['updated_from'],
              fn($q, $v) => $q->whereDate('updated_at', '>=', $v)
            )
            ->when(
              $data['updated_to'],
              fn($q, $v) => $q->whereDate('updated_at', '<=', $v)
            );
        }),
    ];
  }
}
