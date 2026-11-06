<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Support\Constants;

class MediaCollection
{
  public const string MAIN = 'main';
  public const string PREVIEW = 'preview';

  /**
   * Конверсия-миниатюра 96×96 для таблиц и селектов админки.
   *
   * @since 2026-11-06
   */
  public const string THUMB = 'thumb';
}