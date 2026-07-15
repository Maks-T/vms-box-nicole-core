<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\OrderStatuses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Helpers\ProtectDefaultRecord;
use Nicole\Box\Core\Models\OrderStatus;

class OrderStatusForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Section::make(__('Order Status Details'))
        ->schema([
          TextInput::make('name')
            ->label(__('Name'))
            ->required()
            ->translatable(),

          TextInput::make('slug')
            ->label(__('Slug'))
            ->required()
            ->unique(OrderStatus::class, 'slug', ignoreRecord: true)
            ->alphaDash(),

          Select::make('color')
            ->label(__('Color'))
            ->options([
              'gray' => __('Gray'),
              'info' => __('Info'),
              'success' => __('Success'),
              'warning' => __('Warning'),
              'danger' => __('Danger'),
              'primary' => __('Primary'),
            ])
            ->required()
            ->default('gray')
            ->native(false),

          TextInput::make('sort_order')
            ->label(__('Sort'))
            ->required()
            ->numeric()
            ->default(0),

          Toggle::make('is_active')
            ->label(__('Is Active'))
            ->default(true),

          ProtectDefaultRecord::formToggle(OrderStatus::class, 'Default Status')
            ->columnSpanFull(),
        ])
        ->columns(2),
    ]);
  }
}
