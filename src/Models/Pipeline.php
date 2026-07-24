<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Support\Constants\CacheKey;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Eloquent
 */
class Pipeline extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $table = 'pipelines';

  public array $translatable = ['name', 'description'];

  protected $fillable = [
    'code',
    'slug',
    'external_code',
    'name',
    'description',
    'schema',
    'ui_state',
    'is_active',
    'sort_order',
    'settings',
  ];

  protected $casts = [
    'schema' => 'array',
    'ui_state' => 'array',
    'settings' => 'array',
    'is_active' => 'boolean',
    'sort_order' => 'integer',
  ];

  protected static function booted(): void
  {
    static::saved(function (Pipeline $pipeline) {
      cache()->forget(CacheKey::PIPELINE_SCHEMA_PREFIX . $pipeline->external_code);
    });

    static::deleted(function (Pipeline $pipeline) {
      cache()->forget(CacheKey::PIPELINE_SCHEMA_PREFIX . $pipeline->external_code);
    });
  }

  public function scenarios(): HasMany
  {
    return $this->hasMany(PipelineScenario::class, 'pipeline_id');
  }

  public function rules(): HasMany
  {
    return $this->hasMany(BindingRule::class, 'pipeline_id')->orderBy('sort_order');
  }

  public function bindingRules(): HasMany
  {
    return $this->hasMany(BindingRule::class, 'pipeline_id');
  }
}
