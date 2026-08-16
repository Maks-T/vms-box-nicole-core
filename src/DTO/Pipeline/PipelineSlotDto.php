<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Строгий DTO мета-описания слота роли в схеме пайплайна.
 */
readonly class PipelineSlotDto implements Arrayable, JsonSerializable
{
  public function __construct(
    public string  $labelKey,
    public string  $typeCode = 'general',
    public bool    $isRequired = false,
    public bool    $isMultiple = false,
    public ?string $roleCode = null,
  )
  {
  }

  public static function fromArray(array $data, ?string $fallbackRoleCode = null): self
  {
    return new self(
      labelKey: (string)($data['label_key'] ?? ''),
      typeCode: (string)($data['type_code'] ?? 'general'),
      isRequired: (bool)($data['is_required'] ?? false),
      isMultiple: (bool)($data['is_multiple'] ?? false),
      roleCode: isset($data['role_code']) ? (string)$data['role_code'] : $fallbackRoleCode,
    );
  }

  public function toArray(): array
  {
    return [
      'label_key' => $this->labelKey,
      'type_code' => $this->typeCode,
      'is_required' => $this->isRequired,
      'is_multiple' => $this->isMultiple,
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
