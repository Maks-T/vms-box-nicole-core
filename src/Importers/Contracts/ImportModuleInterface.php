<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers\Contracts;

use Illuminate\Console\Command;

interface ImportModuleInterface
{
  /**
   * Название модуля (для вывода в консоль)
   */
  public function getName(): string;

  /**
   * Метод импорта
   *
   * @param array $settings Данные из import_settings.json
   * @param array $data Данные из import_data.json
   * @param Command $command Инстанс консольной команды для вывода прогресса
   */
  public function run(array $settings, array $data, Command $command): void;
}