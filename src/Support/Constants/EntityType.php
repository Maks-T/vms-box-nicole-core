<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class EntityType implements ChoiceConstantInterface
{
  // Базовые сущности каталога и системы
  public const string PRODUCT = 'product';
  public const string PRODUCT_VARIANT = 'product_variant';
  public const string PRODUCT_TYPE = 'product_type';
  public const string FAMILY = 'family';
  public const string CATEGORY = 'category';
  public const string COMPLEX_DICTIONARY = 'complex_dictionary';
  public const string COMPLEX_DICTIONARY_RECORD = 'complex_dictionary_record';
  public const string ATTRIBUTE = 'attribute';
  public const string ATTRIBUTE_OPTION = 'attribute_option';
  public const string WAREHOUSE = 'warehouse';
  public const string UNIT = 'unit';
  public const string PRICE_GROUP = 'price_group';
  public const string PRICE_TYPE = 'price_type';
  public const string CURRENCY = 'currency';
  public const string MEDIA = 'media';
  public const string STOCK = 'stock';
  public const string ORDER = 'order';
  public const string ORDER_SECTION = 'order_section';
  public const string ORDER_STATUS = 'order_status';
  public const string CUSTOMER = 'customer';
  public const string PIPELINE = 'pipeline';

  // Служебный тип для скалярных параметров в пайплайнах
  public const string SCALAR = 'scalar';

  public static function label(string $value): string
  {
    return match ($value) {
      self::PRODUCT                   => __('Product (Physical)'),
      self::PRODUCT_VARIANT           => __('Product Variant (SKU)'),
      self::PRODUCT_TYPE              => __('Product Type'),
      self::FAMILY                    => __('Product Family'),
      self::CATEGORY                  => __('Category'),
      self::COMPLEX_DICTIONARY        => __('Complex Dictionary'),
      self::COMPLEX_DICTIONARY_RECORD => __('Complex Dictionary Record'),
      self::ATTRIBUTE                 => __('Attribute'),
      self::ATTRIBUTE_OPTION          => __('Dictionary Option'),
      self::WAREHOUSE                 => __('Warehouse'),
      self::UNIT                      => __('Unit'),
      self::PRICE_GROUP               => __('Price Group'),
      self::PRICE_TYPE                => __('Price Type'),
      self::CURRENCY                  => __('Currency'),
      self::MEDIA                     => __('Media'),
      self::STOCK                     => __('Stock'),
      self::ORDER                     => __('Order'),
      self::ORDER_SECTION             => __('Order Section'),
      self::ORDER_STATUS              => __('Order Status'),
      self::CUSTOMER                  => __('Customer'),
      self::PIPELINE                  => __('Pipeline'),
      self::SCALAR                    => __('Technical Parameter (Scalar)'),
      default                         => ucfirst(str_replace('_', ' ', $value)),
    };
  }

  /**
   * Полный ассоциативный список всех сущностей системы с их переводами.
   *
   * @return array<string, string> [entity_key => Translatable Label]
   */
  public static function options(): array
  {
    return collect(self::cases())
      ->mapWithKeys(fn (string $case) => [$case => self::label($case)])
      ->toArray();
  }

  public static function cases(): array
  {
    return [
      self::PRODUCT,
      self::PRODUCT_VARIANT,
      self::PRODUCT_TYPE,
      self::FAMILY,
      self::CATEGORY,
      self::COMPLEX_DICTIONARY,
      self::COMPLEX_DICTIONARY_RECORD,
      self::ATTRIBUTE,
      self::ATTRIBUTE_OPTION,
      self::WAREHOUSE,
      self::UNIT,
      self::PRICE_GROUP,
      self::PRICE_TYPE,
      self::CURRENCY,
      self::MEDIA,
      self::STOCK,
      self::ORDER,
      self::ORDER_SECTION,
      self::ORDER_STATUS,
      self::CUSTOMER,
      self::PIPELINE,
      self::SCALAR,
    ];
  }
}
