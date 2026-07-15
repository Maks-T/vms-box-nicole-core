<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Helpers;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class FilterHelper
{
    /** Универсальный фильтр активности */
    public static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label(__('Is active'))
            ->native(false);
    }


    public static function selectFilter(
        string $name,
        string $label,
        array|callable $options,
        ?callable $query = null,
    ): Filter {
        return Filter::make($name)
            ->schema([
                Select::make($name)
                    ->label($label)
                    ->options($options)
                    ->multiple()
                    ->searchable()
                    ->native(false),
            ])
            ->query(
                $query ??
                  function (Builder $query, array $data) use ($name) {
                      return $query->when(
                          $data[$name],
                          fn ($q, $val) => $q->whereIn($name, (array) $val),
                      );
                  },
            );
    }
}
