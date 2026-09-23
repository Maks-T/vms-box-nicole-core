<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Str;
use Nicole\Box\Core\Contracts\OrderSectionFormatterInterface;
use Nicole\Box\Core\Models\OrderSection;

/**
 * Базовый форматтер ядра без отраслевой специфики.
 *
 * @since 2026-09-06
 */
class DefaultOrderSectionFormatter implements OrderSectionFormatterInterface
{
  public function formatSpecifications(OrderSection $section): array
  {
    // @deprecated since 2026-09-06. Поддержка legacy массива description.
    if (!empty($section->description) && is_array($section->description)) {
      return collect($section->description)
        ->mapWithKeys(function ($spec, $k) {
          if (is_array($spec) && isset($spec['name'])) {
            return [$spec['name'] => (string)($spec['description'] ?? '')];
          }
          return [(string)$k => (string)$spec];
        })
        ->toArray();
    }

    $props = $section->meta['properties'] ?? [];
    if (!empty($props) && is_array($props)) {
      return collect($props)
        ->mapWithKeys(fn($val, $key) => [
          Str::headline((string)$key) => is_bool($val) ? ($val ? __('Yes') : __('No')) : (string)$val,
        ])
        ->toArray();
    }

    return [];
  }

  public function formatSummary(OrderSection $section): string
  {
    $specs = $this->formatSpecifications($section);
    if (empty($specs)) {
      return '-';
    }

    return collect($specs)
      ->take(3)
      ->map(fn($val, $key) => "▪ {$key}: {$val}")
      ->join("<br />");
  }

  /**
   * Базовый вывод сметы: строит таблицу строго по сырым данным без допущений.
   *
   * @since 2026-09-06
   */
  public function formatEstimate(OrderSection $section): array
  {
    $estimate = $section->estimate ?? [];
    if (empty($estimate) || !is_array($estimate)) {
      return [TextEntry::make('empty_estimate')->state(__('No estimate data'))->hiddenLabel()];
    }

    $headers = $estimate[0]['value'] ?? [];
    $rows = array_slice($estimate, 1);

    if (empty($rows)) {
      return [TextEntry::make('empty_estimate')->state(__('No estimate data'))->hiddenLabel()];
    }

    $tableColumns = [];
    $textEntries = [];

    // Если первая строка не была заголовком, выводим динамические колонки #1, #2...
    $colCount = !empty($headers) ? count($headers) : count($rows[0]['value'] ?? []);

    for ($i = 0; $i < $colCount; $i++) {
      $colName = $headers[$i] ?? ('#' . ($i + 1));
      $tableColumns[] = TableColumn::make((string)$colName);
      $textEntries[] = TextEntry::make("col_{$i}")->alignEnd($i > 0);
    }

    $flatData = [];
    foreach ($rows as $item) {
      $cells = $item['value'] ?? [];
      $row = [];
      for ($i = 0; $i < $colCount; $i++) {
        $row["col_{$i}"] = $cells[$i] ?? '';
      }
      $flatData[] = $row;
    }

    return [
      RepeatableEntry::make('default_estimate_table')
        ->hiddenLabel()
        ->state($flatData)
        ->table($tableColumns)
        ->schema($textEntries)
        ->columnSpanFull()
    ];
  }
}