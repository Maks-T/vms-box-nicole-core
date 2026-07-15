<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Forms\Tabs;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab as SchemaTab;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\SettingSchema;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SchemaKey;

class SalesChannelsTab
{
  public static function make(string $entityType): SchemaTab
  {
    return SchemaTab::make(__('Sales Channels'))
      ->icon('heroicon-o-globe-alt')
      ->visible(fn () => Channel::where('is_active', true)->exists())
      ->schema([
        Tabs::make('ChannelSettings')
          ->tabs(static::buildChannelTabs($entityType))
          ->columnSpanFull(),
      ]);
  }

  protected static function buildChannelTabs(string $entityType): array
  {
    $channels = Channel::where('is_active', true)->get();
    $schemaRecord = SettingSchema::where('entity_type', $entityType)->first();


    $fieldsConfig = $schemaRecord?->meta_schema ?? [];

    $tabs = [];
    foreach ($channels as $channel) {
      $tabs[] = SchemaTab::make($channel->name)
        ->icon('heroicon-o-queue-list')
        ->schema(static::buildDynamicFields($channel->code, $fieldsConfig))
        ->columns(2);
    }

    return $tabs;
  }

  protected static function buildDynamicFields(string $chCode, array $fields): array
  {
    $components = [];
    $locale = app()->getLocale();

    foreach ($fields as $f) {
      $key = "settings.channels.{$chCode}.{$f[SchemaKey::KEY]}";

      $parsedOptions = [];
      if ($f[SchemaKey::TYPE] === SchemaFieldType::SELECT && isset($f[SchemaKey::OPTIONS])) {
        foreach ($f[SchemaKey::OPTIONS] as $optValue => $optLabel) {
          $parsedOptions[$optValue] = is_array($optLabel) ? ($optLabel[$locale] ?? $optValue) : $optLabel;
        }
      }

      $component = match ($f[SchemaKey::TYPE]) {
        SchemaFieldType::BOOLEAN => Toggle::make($key)
          ->default((bool) ($f[SchemaKey::DEFAULT] ?? false)),

        SchemaFieldType::NUMBER => TextInput::make($key)
          ->numeric()
          ->default($f[SchemaKey::DEFAULT] ?? null),

        SchemaFieldType::SELECT => Select::make($key)
          ->options($parsedOptions)
          ->native(false)
          ->default($f[SchemaKey::DEFAULT] ?? null),

        default => TextInput::make($key)
          ->default($f[SchemaKey::DEFAULT] ?? null),
      };

      $label = is_array($f[SchemaKey::LABEL]) ? ($f[SchemaKey::LABEL][$locale] ?? $f[SchemaKey::KEY]) : $f[SchemaKey::LABEL];

      $components[] = $component
        ->label($label)
        ->columnSpan($f[SchemaKey::WIDTH] ?? 1);
    }

    return $components;
  }

}
