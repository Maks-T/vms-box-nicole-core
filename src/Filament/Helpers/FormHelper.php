<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Helpers;

use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class FormHelper
{
    /**
     * Универсальный генератор slug/code из переводимого поля.
     *
     * @param  string  $targetField  Поле, куда записываем результат ('slug' или 'code')
     * @param  string  $separator  Разделитель ('-' для slug, '_' для code)
     * @param  bool  $onlyOnCreate  Если true, не перезаписывает значение при редактировании
     */
    public static function generateSlug(
        string $targetField = 'slug',
        string $separator = '-',
        bool $onlyOnCreate = true,
    ): \Closure {
        return function ($state, Set $set, ?string $context = null) use (
            $targetField,
            $separator,
            $onlyOnCreate,
        ) {
            if ($onlyOnCreate && $context === 'edit') {
                return;
            }

            if (blank($state)) {
                return;
            }

            $stringToSlug = is_array($state)
              ? $state['ru'] ??
                ($state['en'] ??
                  (collect($state)->flatten()->first(fn ($val) => is_string($val)) ??
                    ''))
              : $state;

            if (is_string($stringToSlug) && filled($stringToSlug)) {
                $set($targetField, Str::slug($stringToSlug, $separator));
            }
        };
    }
}
