<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support;

class CustomerHelper
{
  /**
   * Безопасно разбирает ФИО из одной строки.
   *
   * @param string|null $fullName
   * @return array{first_name: string, last_name: string|null, middle_name: string|null}
   */
  public static function splitFullName(?string $fullName): array
  {
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName ?? ''));

    if (empty($fullName)) {
      return [
        'first_name' => 'Клиент',
        'last_name' => null,
        'middle_name' => null,
      ];
    }

    $parts = explode(' ', $fullName);
    $count = count($parts);

    // Введено одно слово (только имя)
    if ($count === 1) {
      return [
        'first_name' => $parts[0],
        'last_name' => null,
        'middle_name' => null,
      ];
    }

    // Введено два слова (Фамилия и Имя)
    if ($count === 2) {
      return [
        'last_name' => $parts[0],
        'first_name' => $parts[1],
        'middle_name' => null,
      ];
    }

    // Введено три и более слов (Фамилия, Имя, Отчество)
    return [
      'last_name' => $parts[0],
      'first_name' => $parts[1],
      'middle_name' => implode(' ', array_slice($parts, 2)),
    ];
  }

}