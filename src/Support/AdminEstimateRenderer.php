<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

class AdminEstimateRenderer
{
  /**
   * Генерирует древовидную интерактивную смету для административной панели Filament.
   */
  public static function renderTree(array $treeItems): string
  {
    $html = '';

    foreach ($treeItems as $index => $item) {
      $cells = $item['value'] ?? [];

      if ($index === 0 && count($cells) > 0 && str_contains(strtolower($cells[0]), 'название')) {
        continue;
      }

      $name = $cells[0] ?? '—';
      $cellCount = count($cells);
      $totalVal = $cellCount === 2 ? ($cells[1] ?? '') : ($cells[4] ?? ($cells[1] ?? ''));

      $html .= "
      <div x-data='{ isOpen: true }' style='margin-bottom: 20px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05); overflow: hidden; border-left: 4px solid #b8945a;'>
          <div @click='isOpen = !isOpen' style='display: flex; justify-content: space-between; align-items: center; background-color: #f9fafb; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; cursor: pointer; user-select: none;'>
              <div style='display: flex; align-items: center; gap: 8px;'>

                  <div style='width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-right: 4px;'>
                      <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.0' stroke='#b8945a' width='16' height='16' style='transition: transform 0.2s;' :style='isOpen ? \"transform: rotate(0deg);\" : \"transform: rotate(-90deg);\"'>
                          <path stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' />
                      </svg>
                  </div>

                  <span style='font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: #b8945a; letter-spacing: 0.5px;'>{$name}</span>
              </div>
              <span style='font-size: 1rem; font-weight: 800; color: #111827;'>{$totalVal}</span>
          </div>

          <div x-show='isOpen' style='padding: 8px 16px;'>
      ";

      if (!empty($item['children']) && is_array($item['children'])) {
        $html .= "<table style='width: 100%; border-collapse: collapse;'>";
        $html .= self::renderChildRows($item['children'], 1);
        $html .= "</table>";
      } else {
        $html .= "<div style='padding: 10px; text-align: center; color: #9ca3af; font-size: 0.85rem;'>Нет деталей</div>";
      }

      $html .= "
              </div>
          </div>
          ";
    }

    return $html;
  }

  /**
   * Рендеринг вложенных строк внутри карточки
   */
  private static function renderChildRows(array $treeItems, int $depth = 1): string
  {
    $html = '';

    foreach ($treeItems as $item) {
      $cells = $item['value'] ?? [];
      $cellCount = count($cells);
      $name = $cells[0] ?? '—';

      $totalVal = $cellCount === 2 ? ($cells[1] ?? '') : ($cells[4] ?? ($cells[1] ?? ''));

      $indent = $depth > 1 ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth - 1) : '';
      $displayName = $indent . '· ' . $name;

      $html .= "<tr style='border-bottom: 1px solid #f3f4f6;'>";
      $html .= "<td style='padding: 8px 0; font-size: 0.875rem; color: #374151;'>{$displayName}</td>";
      $html .= "<td style='padding: 8px 0; font-size: 0.875rem; font-weight: 600; color: #111827; text-align: right; width: 150px;'>{$totalVal}</td>";
      $html .= "</tr>";

      if (!empty($item['children']) && is_array($item['children'])) {
        $html .= self::renderChildRows($item['children'], $depth + 1);
      }
    }

    return $html;
  }
}
