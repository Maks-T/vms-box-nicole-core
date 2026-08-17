<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Contracts;

use Nicole\Box\Core\Support\Contracts\ChoiceConstantInterface;

/**
 * Контракт класса констант ролей пайплайна для отраслевых пакетов
 */
interface PipelineRoleInterface extends ChoiceConstantInterface
{
  /**
   * Получение целевого типа сущности и целевого кода по умолчанию для роли.
   *
   * @param string $value Код роли (например, 'fixing', 'corner', 'holes')
   * @return array{target_type: string, target_code: string|null}|null
   */
  public static function defaultTarget(string $value): ?array;
}
