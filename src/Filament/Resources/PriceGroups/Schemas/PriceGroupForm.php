<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\PriceGroups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceGroup;

class PriceGroupForm
{
  public static function configure(Schema $schema): Schema
  {
    $priceTypes = PriceType::all();

    $pricingFields = [
      TextInput::make('meta.purchase_cost')
        ->label(__('Base Cost'))
        ->numeric()
        ->default(0)
        ->required(),

      Select::make('meta.purchase_currency')
        ->label(__('Currency'))
        ->options(Currency::where('is_active', true)->pluck('code', 'code'))
        ->default('USD')
        ->native(false)
        ->required(),
    ];

    foreach ($priceTypes as $type) {
      $pricingFields[] = TextInput::make("meta.markup_{$type->slug}")
        ->label(__('Markup for :type (%)', ['type' => (string) $type->name]))
        ->numeric()
        ->suffix('%')
        ->default(0)
        ->required();
    }

    return $schema->components([
      Tabs::make('PriceGroupTabs')
        ->tabs([
          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-identification')
            ->schema([
              Section::make()
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(FormHelper::generateSlug('slug', '-', false))
                    ->translatable(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(PriceGroup::class, 'slug', ignoreRecord: true)
                    ->alphaDash(),

                  Select::make('product_family_id')
                    ->label(__('Product Family'))
                    ->relationship('family', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                  Textarea::make('description')
                    ->label(__('Description'))
                    ->columnSpanFull()
                    ->translatable(),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->columnSpanFull(),
                ])
                ->columns(2),

              Section::make(__('Pricing Settings'))
                ->schema($pricingFields)
                ->columns(2),
            ]),

          SalesChannelsTab::make('price_group'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
