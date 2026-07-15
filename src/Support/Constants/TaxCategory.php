<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;

class TaxCategory implements ChoiceConstantInterface
{
  public const string HARDWARE = 'hardware';
  public const string LABOR = 'labor';
  public const string SOFTWARE = 'software';
  public const string SHIPPING = 'shipping';
  public const string NONE = 'none';

  public static function label(string $value): string
  {
    return match ($value) {
      self::HARDWARE => __('Hardware (Equipment)'),
      self::LABOR => __('Labor (Services)'),
      self::SOFTWARE => __('Software (Licenses)'),
      self::SHIPPING => __('Shipping'),
      self::NONE => __('No Tax (Exempt)'),
      default => '',
    };
  }

  public static function options(): array
  {
    return [
      self::HARDWARE => self::label(self::HARDWARE),
      self::LABOR => self::label(self::LABOR),
      self::SOFTWARE => self::label(self::SOFTWARE),
      self::SHIPPING => self::label(self::SHIPPING),
      self::NONE => self::label(self::NONE),
    ];
  }

  public static function cases(): array
  {
    return [
      self::HARDWARE,
      self::LABOR,
      self::SOFTWARE,
      self::SHIPPING,
      self::NONE,
    ];
  }
}
