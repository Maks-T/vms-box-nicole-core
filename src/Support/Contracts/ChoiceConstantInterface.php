<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Contracts;

interface ChoiceConstantInterface
{
  /**
   * Получить человекочитаемый переведенный лейбл для конкретного значения.
   */
  public static function label(string $value): string;

  /**
   * Получить ассоциативный список опций для выпадающих списков (Filament Select).
   *
   * @return array<string, string> Пример: ['product' => 'Товар', 'service' => 'Услуга']
   */
  public static function options(): array;

  /**
   * Получить плоский список всех возможных значений констант.
   *
   * @return array<int, string> Пример: ['product', 'service', 'bundle']
   */
  public static function cases(): array;
}
