<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nicole\Box\Core\Traits\HasExternalCode;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Eloquent
 */
class BindingRule extends Model
{
    use HasExternalCode;
    use HasTranslations;
    use HasFactory;

    protected $fillable = [
        'external_code',
        'pipeline_id',
        'scenario_id',
        'name',
        'role',
        'parent_type',
        'parent_id',
        'child_type',
        'child_id',
        'conditions',
        'static_meta',
        'quantity_formula',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'static_meta' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(PipelineScenario::class, 'scenario_id');
    }

    public function parent(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'parent_type', 'parent_id');
    }

    public function child(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'child_type', 'child_id');
    }
}
