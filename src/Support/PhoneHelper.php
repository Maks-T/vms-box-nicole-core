<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

class PhoneHelper
{
  /**
   * Приведение номера к международному формату без лишних символов и знака "+".
   * Поддерживает умную нормализацию для России (RU), Беларуси (BY)
   * и сохраняет оригинальный международный код для остальных стран.
   */
  public static function normalize(?string $phone): ?string
  {
    if (empty($phone)) {
      return null;
    }

    // Оставляем только цифры
    $phone = preg_replace('/\D/', '', $phone);

    if (empty($phone)) {
      return null;
    }

    // Мобильные номера Беларуси: 80XXXXXXXXX (11 знаков) -> 375XXXXXXXXX
    if (str_starts_with($phone, '80') && strlen($phone) === 11) {
      return '375' . substr($phone, 2);
    }

    // Мобильные номера России: 8XXXXXXXXXX (11 знаков) -> 7XXXXXXXXXX
    if (str_starts_with($phone, '8') && strlen($phone) === 11) {
      return '7' . substr($phone, 1);
    }

    // Ввод 10-значного мобильного без кода страны (по умолчанию считаем РФ / 7)
    if (strlen($phone) === 10) {
      return '7' . $phone;
    }

    return $phone;
  }

  /**
   * Выделение значимой части номера для надежного поиска в БД между странами.
   */
  public static function forSearch(?string $phone): ?string
  {
    $normalized = self::normalize($phone);

    if (!$normalized) {
      return null;
    }

    // Значимая часть мобильного в РБ - последние 9 цифр (код оператора + номер)
    if (str_starts_with($normalized, '375') && strlen($normalized) >= 9) {
      return substr($normalized, -9);
    }

    // Значимая часть для России и большинства других стран - последние 10 цифр
    if (strlen($normalized) >= 10) {
      return substr($normalized, -10);
    }

    return $normalized;
  }

}