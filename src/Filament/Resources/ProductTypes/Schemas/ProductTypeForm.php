<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\ProductTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Support\Constants\SchemaFieldType;
use Nicole\Box\Core\Support\Constants\SchemaKey;

class ProductTypeForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('ProductTypeTabs')
        ->tabs([

          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-information-circle')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                      if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                        $set('code', \Illuminate\Support\Str::slug($state, '_'));
                        $set('slug', \Illuminate\Support\Str::slug($state, '-'));
                      }
                    })
                    ->translatable(),

                  TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->helperText(__('Used for clean URLs (SEO)')),

                  Select::make('family_id')
                    ->label(__('Product Family'))
                    ->relationship('family', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                  TextInput::make('icon')
                    ->label(__('Icon'))
                    ->placeholder('heroicon-o-cube'),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->columnSpanFull(),
                ])
                ->columns(2),
            ]),

          Tabs\Tab::make(__('Technical Specifications'))
            ->icon('heroicon-o-adjustments-vertical')
            ->visible(function (Get $get) {
              $familyId = $get('family_id');
              return $familyId && ProductFamily::find($familyId)?->meta_schema;
            })
            ->schema(function (Get $get) {
              $familyId = $get('family_id');
              $schema = ProductFamily::find($familyId)?->meta_schema ?? [];
              $components = [];
              $locale = app()->getLocale();

              foreach ($schema as $field) {
                $key = "meta.{$field[SchemaKey::KEY]}";
                $label = is_array($field[SchemaKey::LABEL])
                  ? ($field[SchemaKey::LABEL][$locale] ?? $field[SchemaKey::KEY])
                  : ($field[SchemaKey::LABEL] ?? $field[SchemaKey::KEY]);

                $input = match ($field[SchemaKey::TYPE]) {
                  SchemaFieldType::BOOLEAN => Toggle::make($key)->inline(false),
                  SchemaFieldType::NUMBER => TextInput::make($key)->numeric(),
                  default => TextInput::make($key),
                };

                $components[] = $input->label($label)->columnSpan($field[SchemaKey::WIDTH] ?? 1);
              }

              return [
                Section::make(__('Family Parameters'))
                  ->schema($components)
                  ->columns(2)
              ];
            }),

          SalesChannelsTab::make('product_type'),
        ])
        ->columnSpanFull(),
    ]);
  }

}
