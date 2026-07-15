<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Helpers;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class TableHelper
{
  /**
   * Универсальная колонка превью изображения (с поддержкой "стопки" для вариаций)
   */
  public static function photoColumn(
    string $name = 'preview_image',
  ): ImageColumn {
    return ImageColumn::make($name)
      ->label(__('Photo'))
      ->state(function (Model $record) {
        $urls = [];


        /*if (method_exists($record, 'getPreviewUrl') && $url = $record->getPreviewUrl()) {
          $urls[] = str_starts_with($url, 'http') ? $url : url($url);
        }*/

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
      ->limit(3);
  }

  /** Стандартная колонка статуса (is_active) */
  public static function statusColumn(string $name = 'is_active'): IconColumn
  {
    return IconColumn::make($name)
      ->label(__('Status'))
      ->boolean()
      ->toggleable();
  }

  /** Колонка для системных кодов (slug/code) */
  public static function codeColumn(string $name = 'slug'): TextColumn
  {
    return TextColumn::make($name)
      ->label(__('Code'))
      ->fontFamily('mono')
      ->color('gray')
      ->toggleable(isToggledHiddenByDefault: true)
      ->searchable();
  }
}
