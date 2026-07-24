<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;

class PipelineScenario extends Model
{
  use HasExternalCode;
  use HasFactory;

  protected $table = 'pipeline_scenarios';

  protected $fillable = [
    'pipeline_id',
    'code',
    'external_code',
    'name',
    'description',
    'ui_state',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name', 'description'];

  protected function casts(): array
  {
    return [
      'ui_state' => 'array',
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  public function pipeline(): BelongsTo
  {
    return $this->belongsTo(Pipeline::class);
  }

  public function rules(): HasMany
  {
    return $this->hasMany(BindingRule::class, 'scenario_id')->orderBy('sort_order');
  }
}
