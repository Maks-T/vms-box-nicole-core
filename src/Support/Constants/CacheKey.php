<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

class CacheKey
{
  // Ключи кэша инфраструктуры валют
  public const string CURRENCIES_LIST = 'core_currencies_list';
  public const string BASE_CURRENCY = 'core_base_currency';

  // Ключи и теги кэша каталога
  public const string CATALOG_VERSION = 'catalog_version'; // для кэша на диске
  public const string CATALOG_TAG = 'catalog'; //для кэша в оперативке redis

  // Кэш схем пайплайнов
  public const string PIPELINE_SCHEMA_PREFIX = 'pipeline_schema_';
}
