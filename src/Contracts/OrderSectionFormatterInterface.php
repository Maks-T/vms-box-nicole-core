<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Contracts;

use Filament\Schemas\Components\Component as SchemasComponent;
use Nicole\Box\Core\Models\OrderSection;

/**
 * Контракт для форматирования изделий заказа под конкретную индустрию.
 *
 * @since 2026-09-06
 */
interface OrderSectionFormatterInterface
{
  /**
   * Список характеристик для инфолистов и модалок (ключ => значение).
   */
  public function formatSpecifications(OrderSection $section): array;

  /**
   * Краткая сводка для таблицы списка изделий.
   */
  public function formatSummary(OrderSection $section): string;

  /**
   * Схема компонентов вкладки «Смета» для Filament.
   *
   * @param OrderSection $section
   * @return array<SchemasComponent>
   *
   * @since 2026-09-06
   */
  public function formatEstimate(OrderSection $section): array;

}