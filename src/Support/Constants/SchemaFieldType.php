<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;

class SchemaFieldType implements ChoiceConstantInterface
{
  public const string TEXT = 'text';
  public const string NUMBER = 'number';
  public const string BOOLEAN = 'boolean';
  public const string SELECT = 'select';

  public static function label(string $value): string
  {
    return match ($value) {
      self::TEXT => __('String'),
      self::NUMBER => __('Numeric'),
      self::BOOLEAN => __('Boolean (Toggle)'),
      self::SELECT => __('Dictionary (Select)'),
      default => '',
    };
  }

  public static function options(): array
  {
    return [
      self::TEXT => self::label(self::TEXT),
      self::NUMBER => self::label(self::NUMBER),
      self::BOOLEAN => self::label(self::BOOLEAN),
      self::SELECT => self::label(self::SELECT),
    ];
  }

  public static function cases(): array
  {
    return [
      self::TEXT,
      self::NUMBER,
      self::BOOLEAN,
      self::SELECT,
    ];
  }

}
