<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;

class BrandColorSchema
{
    public static function make(string $name = 'primary_color'): Select
    {
        $options = [];
        $colorNames = [
            'Slate',
            'Gray',
            'Zinc',
            'Neutral',
            'Stone',
            'Red',
            'Orange',
            'Amber',
            'Yellow',
            'Lime',
            'Green',
            'Emerald',
            'Teal',
            'Cyan',
            'Sky',
            'Blue',
            'Indigo',
            'Violet',
            'Purple',
            'Fuchsia',
            'Pink',
            'Rose',
        ];

        foreach ($colorNames as $colorName) {
            $colorArray = constant(Color::class.'::'.$colorName);
            $oklchString = $colorArray[500] ?? 'oklch(0 0 0)';
            $cssColor = Color::convertToRgb($oklchString);
            $options[$colorName] = "
        <div style='display: flex; align-items: center; gap: 0.75rem;'>
          <div style='background-color: {$cssColor}; width: 1.25rem; height: 1.25rem; border-radius: 9999px; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);'></div>
          <span style='font-weight: 500; font-size: 0.875rem;'>{$colorName}</span>
        </div>
      ";
        }

        return Select::make($name)
            ->label(__('Brand Color'))
            ->helperText(
                __('This color will be used for your buttons and interface elements.'),
            )
            ->options($options)
            ->default('Teal')
            ->searchable()
            ->allowHtml()
            ->extraAttributes(['style' => 'max-width: 320px;']);
    }
}
