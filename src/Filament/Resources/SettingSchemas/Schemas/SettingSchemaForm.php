<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\SettingSchemas\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Config;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SchemaKey;

class SettingSchemaForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Section::make(__('Channel Settings Schema Builder'))
        ->description(__('Define which fields will be available for this entity in different Sales Channels.'))
        ->schema([

          Select::make('entity_type')
            ->label(__('Target Entity'))
            ->options(function () {
              $entities = Config::get('nicole.setting_entities', []);
              return collect($entities)
                ->map(fn(string $label) => __($label))
                ->toArray();
            })
            ->required()
            ->unique(ignoreRecord: true)
            ->native(false),

          Repeater::make('meta_schema')
            ->label(__('Fields Configuration'))
            // Конвертируем плоский JSON из базы в структуру репитера при загрузке формы
            ->formatStateUsing(function ($state) {
              if (!is_array($state)) return [];
              $result = [];
              foreach ($state as $item) {
                // Раскладываем общее поле default по виртуальным полям в зависимости от типа
                $type = $item[SchemaKey::TYPE] ?? SchemaFieldType::TEXT;
                $default = $item[SchemaKey::DEFAULT] ?? null;

                $item['default_boolean'] = $type === SchemaFieldType::BOOLEAN ? (bool)$default : false;
                $item['default_select'] = $type === SchemaFieldType::SELECT ? $default : null;
                $item['default_text'] = in_array($type, [SchemaFieldType::TEXT, SchemaFieldType::NUMBER]) ? $default : null;

                $result[] = $item;
              }
              return $result;
            })
            // Перед сохранением склеиваем виртуальные поля обратно в один чистый ключ 'default'
            ->dehydrateStateUsing(function ($state) {
              if (!is_array($state)) return [];
              foreach ($state as &$item) {
                $type = $item[SchemaKey::TYPE] ?? SchemaFieldType::TEXT;

                if ($type === SchemaFieldType::BOOLEAN) {
                  $item[SchemaKey::DEFAULT] = (bool) ($item['default_boolean'] ?? false);
                } elseif ($type === SchemaFieldType::SELECT) {
                  $item[SchemaKey::DEFAULT] = $item['default_select'] ?? null;
                } else {
                  $item[SchemaKey::DEFAULT] = $item['default_text'] ?? null;
                }

                // Удаляем временные поля, чтобы они не попадали в БД
                unset($item['default_boolean'], $item['default_select'], $item['default_text']);
              }
              return $state;
            })
            ->schema([
              TextInput::make(SchemaKey::KEY)
                ->label(__('Key (System)'))
                ->placeholder('is_collapsed')
                ->required()
                ->alphaDash(),

              TextInput::make(SchemaKey::LABEL)
                ->label(__('Label (Human readable)'))
                ->required()
                ->translatable(),

              Select::make(SchemaKey::TYPE)
                ->label(__('Field Type'))
                // Используем умные опции из SchemaFieldType
                ->options(SchemaFieldType::options())
                ->required()
                ->live()
                ->native(false),

              Select::make(SchemaKey::WIDTH)
                ->label(__('UI Width'))
                ->options([
                  1 => __('Minimum Part'),
                  2 => __('Full Width'),
                ])
                ->default(1),

              // Дефолт для Boolean (Toggle)
              Toggle::make('default_boolean')
                ->label(__('Default Value'))
                ->visible(fn (Get $get) => $get(SchemaKey::TYPE) === SchemaFieldType::BOOLEAN)
                ->dehydrated(false),

              // Дефолт для Numeric / String
              TextInput::make('default_text')
                ->label(__('Default Value'))
                ->visible(fn (Get $get) => in_array($get(SchemaKey::TYPE), [SchemaFieldType::TEXT, SchemaFieldType::NUMBER]))
                ->dehydrated(false),

              // Дефолт для Select (Dictionary)
              Select::make('default_select')
                ->label(__('Default Value'))
                ->options(function (Get $get) {
                  $rawOptions = $get(SchemaKey::OPTIONS) ?? [];
                  $result = [];

                  // Сценарий А: Если форма только загрузилась и в $get('options') лежит сырой плоский массив из БД
                  if (is_array($rawOptions) && !isset($rawOptions[0])) {
                    $locale = app()->getLocale();
                    foreach ($rawOptions as $key => $label) {
                      $result[$key] = is_array($label)
                        ? ($label[$locale] ?? $key)
                        : $label;
                    }
                  } else {
                    // Сценарий Б: Если репитер формы уже активен и возвращает структурированный массив
                    foreach ($rawOptions as $opt) {
                      if (!empty($opt[SchemaKey::KEY])) {
                        $result[$opt[SchemaKey::KEY]] = is_array($opt[SchemaKey::LABEL])
                          ? ($opt[SchemaKey::LABEL][app()->getLocale()] ?? $opt[SchemaKey::KEY])
                          : ($opt[SchemaKey::LABEL] ?? $opt[SchemaKey::KEY]);
                      }
                    }
                  }

                  return $result;
                })
                ->visible(fn (Get $get) => $get(SchemaKey::TYPE) === SchemaFieldType::SELECT)
                ->native(false)
                ->dehydrated(false),

              Repeater::make(SchemaKey::OPTIONS)
                ->label(__('Dictionary Options'))
                ->visible(fn(Get $get) => $get(SchemaKey::TYPE) === SchemaFieldType::SELECT)
                ->schema([
                  TextInput::make(SchemaKey::KEY)
                    ->label(__('Value (System)'))
                    ->required()
                    ->alphaDash(),

                  TextInput::make(SchemaKey::LABEL)
                    ->label(__('Label (Human readable)'))
                    ->required()
                    ->translatable(),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel(__('Add Option'))
                ->reorderable(false)

                ->formatStateUsing(function ($state) {
                  if (!is_array($state)) return [];
                  $result = [];
                  foreach ($state as $key => $label) {
                    $result[] = [
                      SchemaKey::KEY => $key,
                      SchemaKey::LABEL => $label,
                    ];
                  }
                  return $result;
                })

                ->dehydrateStateUsing(function ($state) {
                  $result = [];
                  if (!is_array($state)) return $result;
                  foreach ($state as $item) {
                    if (!empty($item[SchemaKey::KEY])) {
                      $result[$item[SchemaKey::KEY]] = $item[SchemaKey::LABEL] ?? $item[SchemaKey::KEY];
                    }
                  }
                  return $result;
                }),
            ])
            ->columns(5)
            ->addActionLabel(__('Add Field'))
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn(array $state): ?string => $state[SchemaKey::KEY] ?? null),
        ])->columnSpanFull(),
    ]);
  }

}
