<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Support\Constants\CacheKey;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pipeline extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'code',
    'slug',
    'external_code',
    'name',
    'industry',
    'description',
    'ui_state',
    'schema',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name', 'description'];

  protected function casts(): array
  {
    return [
      'ui_state' => 'array',
      'schema' => 'array',
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  protected static function booted(): void
  {
    static::saved(function (Pipeline $pipeline) {
      cache()->forget(CacheKey::PIPELINE_SCHEMA_PREFIX . $pipeline->external_code);
    });

    static::deleted(function (Pipeline $pipeline) {
      cache()->forget(CacheKey::PIPELINE_SCHEMA_PREFIX . $pipeline->external_code);
    });
  }

  public function rules(): HasMany
  {
    return $this->hasMany(BindingRule::class)->orderBy('sort_order');
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\PipelineFactory
  {
    return \Nicole\Box\Core\Database\Factories\PipelineFactory::new();
  }
}
