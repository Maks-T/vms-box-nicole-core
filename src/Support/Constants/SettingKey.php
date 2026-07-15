<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

class SettingKey
{
  // Базовые системные флаги видимости
  public const string IS_PUBLIC = 'is_public';
  public const string IS_SETTINGS_PUBLIC = 'is_settings_public';
  public const string IS_ENABLED = 'is_enabled';

  // Системные флаги для UI и фильтров
  public const string SHOW_IN_MENU = 'show_in_menu';
  public const string IS_FILTERABLE = 'is_filterable';
  public const string IS_COLLAPSED = 'is_collapsed';
  public const string FILTER_TYPE = 'filter_type';

}
