<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Categories\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Nicole\Box\Core\Filament\Forms\Tabs\SalesChannelsTab;
use Nicole\Box\Core\Filament\Helpers\FormHelper;
use Nicole\Box\Core\Models\Category;

class CategoryForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema->components([
      Tabs::make('CategoryTabs')
        ->tabs([
          Tabs\Tab::make(__('General Information'))
            ->icon('heroicon-o-information-circle')
            ->schema([
              Section::make(__('Category Details'))
                ->schema([
                  TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                      FormHelper::generateSlug('slug', '-', false),
                    )
                    ->translatable(),

                  TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(Category::class, 'slug', ignoreRecord: true)
                    ->alphaDash(),

                  
                  TextInput::make('external_code')
                    ->label(__('External Code'))
                    ->nullable()
                    ->helperText(__('Used for ERP / 1C integration')),

                  SelectTree::make('parent_id')
                    ->label(__('Parent Category'))
                    ->relationship('parent', 'name', 'parent_id')
                    ->enableBranchNode()
                    ->searchable()
                    ->placeholder(__('Select parent...')),

                  Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->columnSpanFull(),

                  Textarea::make('description')
                    ->label(__('Description'))
                    ->columnSpanFull()
                    ->translatable(),
                ])
                ->columns(2),
            ]),

          SalesChannelsTab::make('category'),
        ])
        ->columnSpanFull(),
    ]);
  }
}
