<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Traits;

trait HasLocalizedJson
{
  public function getLocalizedJson(string $attribute): array
  {
    $data = $this->getAttribute($attribute);

    if (!is_array($data)) {
      return [];
    }

    return $this->localizeArrayRecursive($data);
  }

  protected function localizeArrayRecursive(array $array): array
  {
    $locale = app()->getLocale();

    if ($this->isTranslationDictionary($array)) {
      return (array) ($array[$locale] ?? '');
    }

    $result = [];

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        if ($this->isTranslationDictionary($value)) {
          // Вытаскиваем строку для текущего языка
          $result[$key] = $value[$locale] ?? (reset($value) ?: '');
        } else {
          $result[$key] = $this->localizeArrayRecursive($value);
        }
      } else {
        $result[$key] = $value;
      }
    }

    return $result;
  }

  /**
   * Проверка: является ли массив словарем переводов.
   */
  protected function isTranslationDictionary(array $array): bool
  {
    if (empty($array)) {
      return false;
    }

    $locales = config('nicole.locales', ['ru', 'en']);

    // Если хотя бы один ключ массива является известной локалью
    return count(array_intersect(array_keys($array), $locales)) > 0;
  }

}
