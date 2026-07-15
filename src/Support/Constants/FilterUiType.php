<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;

class FilterUiType implements ChoiceConstantInterface
{
  public const string CHECKBOX = 'checkbox';
  public const string SELECT = 'select';
  public const string COLOR = 'color';
  public const string RANGE = 'range';

  public static function label(string $value): string
  {
    return match ($value) {
      self::CHECKBOX => __('Checkbox'),
      self::SELECT => __('Select'),
      self::COLOR => __('Color'),
      self::RANGE => __('Range'),
      default => '',
    };
  }

  public static function options(): array
  {
    return [
      self::CHECKBOX => self::label(self::CHECKBOX),
      self::SELECT => self::label(self::SELECT),
      self::COLOR => self::label(self::COLOR),
      self::RANGE => self::label(self::RANGE),
    ];
  }

  public static function cases(): array
  {
    return [
      self::CHECKBOX,
      self::SELECT,
      self::COLOR,
      self::RANGE,
    ];
  }
}
