<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;

class CatalogType implements ChoiceConstantInterface
{
  public const string PRODUCT = 'product';
  public const string SERVICE = 'service';
  public const string BUNDLE = 'bundle';

  public static function label(string $value): string
  {
    return match ($value) {
      self::PRODUCT => __('Product (Physical)'),
      self::SERVICE => __('Service / Work'),
      self::BUNDLE => __('Bundle (Kit)'),
      default => '',
    };
  }

  public static function options(): array
  {
    return [
      self::PRODUCT => self::label(self::PRODUCT),
      self::SERVICE => self::label(self::SERVICE),
      self::BUNDLE => self::label(self::BUNDLE),
    ];
  }

  public static function cases(): array
  {
    return [
      self::PRODUCT,
      self::SERVICE,
      self::BUNDLE,
    ];
  }
}
