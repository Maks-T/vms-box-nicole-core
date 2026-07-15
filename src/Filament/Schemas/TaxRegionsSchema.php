<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Nicole\Box\Core\Support\Constants\TaxCategory;

class TaxRegionsSchema
{
  public static function make(string $name = 'tax_regions'): Repeater
  {
    $fields = [
      TextInput::make('name')
        ->label(__('Region Name (e.g. Dallas, TX)'))
        ->required()
        ->columnSpanFull(),
    ];

    foreach (TaxCategory::cases() as $category) {
      if ($category === TaxCategory::NONE) {
        continue;
      }

      $fields[] = TextInput::make($category)
        ->label(TaxCategory::label($category) . ' (%)')
        ->numeric()
        ->step(0.001)
        ->default(0);
    }

    $fields[] = Toggle::make('is_default')
      ->label(__('Default Region'))
      ->helperText(__('Used automatically in new estimates.'))
      ->default(false)
      ->columnSpanFull();

    return Repeater::make($name)
      ->label(__('Tax Regions Management'))
      ->schema($fields)
      ->columns(2)
      ->defaultItems(1)
      ->addActionLabel(__('Add Tax Region'))
      ->reorderable(true)
      ->collapsible()
      ->itemLabel(fn(array $state): ?string => $state['name'] ?? null);
  }

}
