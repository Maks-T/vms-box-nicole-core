<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Resources\Orders\Schemas\Tabs;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Nicole\Box\Core\Models\OrderSection;

class EstimateTab
{
  /**
   * Генерация нативной вкладки сметы с динамической структурой колонок и рекурсивной вложенностью
   */
  public static function make(): Tab
  {
    return Tab::make(__('Estimate'))
      ->icon('heroicon-o-currency-dollar')
      ->schema(function (OrderSection $record) {
        $estimate = $record->estimate ?? [];
        if (empty($estimate)) {
          return [TextEntry::make('empty_estimate')->state('Смета пуста')->hiddenLabel()];
        }

        // Динамически определяем заголовки колонок из первой строки сметы
        $headers = $estimate[0]['value'] ?? [];
        if (empty($headers)) {
          $headers = ['Наименование', 'Кол-во', 'Ед.изм.', 'Цена', 'Стоимость'];
        }

        $sections = [];

        foreach (array_slice($estimate, 1) as $index => $node) {
          $sectionComponent = self::parseNode($node, "estimate_node_{$index}", $headers);
          if ($sectionComponent) {
            $sections[] = $sectionComponent;
          }
        }

        return $sections;
      });
  }

  /**
   * Рекурсивный обход узлов дерева сметы для построения вложенных секций и таблиц
   */
  /**
   * Рекурсивный обход узлов дерева сметы для построения вложенных секций и таблиц
   */
  protected static function parseNode(array $node, string $stateKey, array $headers): ?Section
  {
    $cells = $node['value'] ?? [];
    if (empty($cells)) {
      return null;
    }

    $title = $cells[0] ?? '-';
    $totalVal = count($cells) > 1 ? end($cells) : '';

    $children = $node['children'] ?? [];
    if (empty($children)) {
      return null; // Пропускаем пустые категории без элементов
    }

    // Проверяем, есть ли у дочерних элементов свои вложенные подгруппы
    $hasSubgroups = collect($children)->contains(fn($child) => !empty($child['children']));

    $schema = [];

    if ($hasSubgroups) {
      // Рекурсивный запуск для всех вложенных подкатегорий
      foreach ($children as $subIndex => $subNode) {
        $subSection = self::parseNode($subNode, "{$stateKey}_sub_{$subIndex}", $headers);
        if ($subSection) {
          $schema[] = $subSection;
        }
      }
    } else {
      // Преобразуем плоский список элементов в формат для RepeatableEntry
      $flatData = [];
      foreach ($children as $child) {
        $childCells = $child['value'] ?? [];
        if (empty($childCells)) {
          continue;
        }

        $row = [];
        foreach ($headers as $colIndex => $colName) {
          $row["col_{$colIndex}"] = $childCells[$colIndex] ?? '';
        }
        $flatData[] = $row;
      }

      // Формируем структуру колонок таблицы на основе заголовков из калькулятора
      $tableColumns = [];
      $textEntries = [];

      foreach ($headers as $colIndex => $colName) {
        // ИСПРАВЛЕНО: Текст заголовка передается напрямую в make(), без вызова ->label()
        $tableColumns[] = TableColumn::make($colName);

        $entry = TextEntry::make("col_{$colIndex}");

        if ($colIndex > 0) {
          $entry->alignEnd(); // Все числовые колонки выравниваем по правому краю
        }

        if ($colIndex === count($headers) - 1) {
          $entry->weight('bold')->color('success'); // Итоговую стоимость делаем жирной и зеленой
        }

        $textEntries[] = $entry;
      }

      $schema[] = RepeatableEntry::make($stateKey)
        ->hiddenLabel()
        ->state($flatData)
        ->table($tableColumns)
        ->schema($textEntries)
        ->columnSpanFull();
    }

    return Section::make(trim("{$title} {$totalVal}"))
      ->collapsible()
      ->collapsed(false)
      ->schema($schema);
  }
}
