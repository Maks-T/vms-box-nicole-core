<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Helpers;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TableHelper
{
  /**
   * Скрытая по умолчанию колонка ID
   */
  public static function idColumn(): TextColumn
  {
    return TextColumn::make('id')
      ->label('ID')
      ->sortable()
      ->toggleable(isToggledHiddenByDefault: true);
  }

  /**
   * Универсальная колонка превью изображения (с поддержкой "стопки" для вариаций)
   */
  public static function photoColumn(
    string $name = 'preview_image',
  ): ImageColumn
  {
    return ImageColumn::make($name)
      ->label(__('Photo'))
      ->state(function (Model $record) {
        $urls = [];

        // Добавляем картинки вариаций
        if ($record->relationLoaded('variants') && $record->variants) {
          foreach ($record->variants as $variant) {
            if (method_exists($variant, 'getPreviewUrl') && $vUrl = $variant->getPreviewUrl()) {
              $urls[] = str_starts_with($vUrl, 'http') ? $vUrl : url($vUrl);
            }
          }
        }

        if (empty($urls)) {
          return null;
        }

        return array_slice(array_unique($urls), 0, 3);
      })
      ->circular()
      ->stacked()
      ->limit(3)
      ->toggleable(); // Делаем колонку фото управляемой
  }

  /**
   * Скрытая по умолчанию колонка внешнего кода ERP/1C
   */
  public static function externalCodeColumn(): TextColumn
  {
    return TextColumn::make('external_code')
      ->label(__('External Code'))
      ->fontFamily('mono')
      ->color('gray')
      ->toggleable(isToggledHiddenByDefault: true)
      ->searchable();
  }

  /**
   * Интерактивная колонка статуса (is_active) с перехватом ошибок сохранения
   */
  public static function statusColumn(string $name = 'is_active'): ToggleColumn
  {
    return ToggleColumn::make($name)
      ->label(__('Activity'))
      ->updateStateUsing(function (Model $record, $state) use ($name) {
        try {
          $record->update([$name => $state]);

          if (method_exists($record, 'product')) {
            $record->product?->refreshMinPrice();
          }

          Notification::make()
            ->success()
            ->title(__('Status updated'))
            ->send();
        } catch (\Throwable $e) {
          Notification::make()
            ->danger()
            ->title(__('Error saving status'))
            ->body($e->getMessage())
            ->persistent()
            ->send();
        }
      })
      ->toggleable();
  }

  /**
   * Колонка для системных кодов (slug/code)
   */
  public static function codeColumn(string $name = 'slug'): TextColumn
  {
    return TextColumn::make($name)
      ->label(__('Code'))
      ->fontFamily('mono')
      ->color('gray')
      ->toggleable(isToggledHiddenByDefault: true)
      ->searchable();
  }

  /**
   * Скрытая по умолчанию колонка сортировки
   */
  public static function sortOrderColumn(): TextColumn
  {
    return TextColumn::make('sort_order')
      ->label(__('Sort'))
      ->numeric()
      ->toggleable(isToggledHiddenByDefault: true)
      ->sortable();
  }

  /**
   * Скрытая по умолчанию колонка даты создания
   */
  public static function createdAtColumn(): TextColumn
  {
    return TextColumn::make('created_at')
      ->label(__('Created At'))
      ->dateTime()
      ->toggleable(isToggledHiddenByDefault: true)
      ->sortable();
  }

  /**
   * Скрытая по умолчанию колонка даты обновления
   */
  public static function updatedAtColumn(): TextColumn
  {
    return TextColumn::make('updated_at')
      ->label(__('Updated At'))
      ->dateTime()
      ->toggleable(isToggledHiddenByDefault: true)
      ->sortable();
  }
}
