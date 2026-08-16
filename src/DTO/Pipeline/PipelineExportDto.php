<?php

declare(strict_types=1);

namespace Nicole\Box\Core\DTO\Pipeline;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;
use Nicole\Box\Core\Models\Pipeline;

readonly class PipelineExportDto implements Arrayable, JsonSerializable
{
  /**
   * @param Collection<int, BindingRuleExportDto> $rules
   */
  public function __construct(
    public int        $id,
    public string     $code,
    public string     $name,
    public ?string    $industry,
    public Collection $rules,
  )
  {
  }

  public static function fromModel(Pipeline $pipeline, ?string $locale = null): self
  {
    $locale ??= app()->getLocale();
    $name = $pipeline->getTranslation('name', $locale) ?: (string)$pipeline->name;

    $rules = $pipeline->rules->map(
      fn($rule) => BindingRuleExportDto::fromModel($rule),
    );

    return new self(
      id: (int)$pipeline->id,
      code: (string)$pipeline->code,
      name: $name,
      industry: $pipeline->industry ?? null,
      rules: $rules,
    );
  }

  public function toArray(): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'industry' => $this->industry,
      'rules' => $this->rules->map(fn($r) => $r->toArray())->toArray(),
    ];
  }

  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
