<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Nicole\Box\Core\Support\Constants\EntityType as ET;

/**
 * Строгий DTO мета-описания слота роли в схеме пайплайна.
 */
readonly class PipelineSlotDto implements Arrayable, JsonSerializable
{
  /**
   * @param string|array<string, string> $labelKey Строка или мультиязычный массив переводов
   */
  public function __construct(
    public string|array $labelKey,
    public string       $targetType = ET::PRODUCT_TYPE,
    public ?string      $targetCode = null,
    public bool         $isRequired = false,
    public bool         $isMultiple = false,
    public ?string      $roleCode = null,
  )
  {
  }

  public static function fromArray(array $data, ?string $fallbackRoleCode = null): self
  {
    $rawCode = $data['target_code'] ?? ($data['type_code'] ?? null);
    $rawTargetType = (string)($data['target_type'] ?? ($data['entity_type'] ?? ''));

    if ($rawTargetType === '') {
      $targetType = ($rawCode === null || $rawCode === '' || $rawCode === 'general')
        ? ET::SCALAR
        : ET::PRODUCT_TYPE;
    } else {
      $targetType = $rawTargetType;
    }

    $targetCode = ($targetType === ET::SCALAR || $rawCode === 'general' || $rawCode === '')
      ? null
      : (string)$rawCode;

    $labelKey = $data['label_key'] ?? '';

    return new self(
      labelKey: $labelKey,
      targetType: $targetType,
      targetCode: $targetCode,
      isRequired: (bool)($data['is_required'] ?? false),
      isMultiple: (bool)($data['is_multiple'] ?? false),
      roleCode: isset($data['role_code']) ? (string)$data['role_code'] : $fallbackRoleCode,
    );
  }

  /**
   * Извлечение локализованной строки для API.
   */
  public function getLocalizedLabel(?string $locale = null): string
  {
    if (is_string($this->labelKey)) {
      return $this->labelKey;
    }

    $locale ??= app()->getLocale();
    return (string)($this->labelKey[$locale] ?? ($this->labelKey['ru'] ?? head($this->labelKey)));
  }

  /**
   * Сериализация в массив для API-ресурсов схемы пайплайна.
   *
   * @return array{
   *   label_key: string,
   *   target_type: string,
   *   target_code: string|null,
   *   is_required: bool,
   *   is_multiple: bool
   * }
   */
  public function toArray(): array
  {
    return [
      'label_key' => $this->getLocalizedLabel(),
      'target_type' => $this->targetType,
      'target_code' => $this->targetCode,
      'is_required' => $this->isRequired,
      'is_multiple' => $this->isMultiple,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }

}
