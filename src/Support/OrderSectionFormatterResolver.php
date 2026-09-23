<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

use Filament\Schemas\Components\Component as FilamentSchemasComponent;
use Nicole\Box\Core\Contracts\OrderSectionFormatterInterface;
use Nicole\Box\Core\Models\OrderSection;

/**
 * Центральный диспетчер отраслевых обработчиков форматирования изделий заказа.
 *
 * @since 2026-09-06
 */
class OrderSectionFormatterResolver
{
  /**
   * @var array<string, class-string<OrderSectionFormatterInterface>>
   */
  protected static array $formatters = [];

  /**
   * Регистрация отраслевого форматтера для типа секции.
   *
   * @param string $type Код типа изделия ('terrace', 'fence', 'worktop' и др.)
   * @param class-string<OrderSectionFormatterInterface> $formatterClass
   * @return void
   *
   * @since 2026-09-06
   */
  public static function register(string $type, string $formatterClass): void
  {
    self::$formatters[$type] = $formatterClass;
  }

  /**
   * Получение активного форматтера для изделия или базовой реализации ядра.
   *
   * @param OrderSection $section
   * @return OrderSectionFormatterInterface
   *
   * @since 2026-09-06
   */
  public static function getFormatter(OrderSection $section): OrderSectionFormatterInterface
  {
    $type = (string)($section->type ?? 'default');

    if (isset(self::$formatters[$type]) && class_exists(self::$formatters[$type])) {
      return app(self::$formatters[$type]);
    }

    return new DefaultOrderSectionFormatter();
  }

  /**
   * Получение полного списка характеристик изделия для модального окна.
   *
   * @param OrderSection $section
   * @return array<string, string>
   *
   * @since 2026-09-06
   */
  public static function formatSpecifications(OrderSection $section): array
  {
    return self::getFormatter($section)->formatSpecifications($section);
  }

  /**
   * Получение краткой сводки характеристик для таблицы списка изделий.
   *
   * @param OrderSection $section
   * @return string
   *
   * @since 2026-09-06
   */
  public static function formatSummary(OrderSection $section): string
  {
    return self::getFormatter($section)->formatSummary($section);
  }

  /**
   * Схема компонентов вкладки сметы для Filament.
   *
   * @param OrderSection $section
   * @return array<FilamentSchemasComponent>
   *
   * @since 2026-09-06
   */
  public static function formatEstimate(OrderSection $section): array
  {
    return self::getFormatter($section)->formatEstimate($section);
  }
}