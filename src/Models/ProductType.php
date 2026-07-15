<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductType extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'code',
    'external_code',
    'slug',
    'family_id',
    'name',
    'icon',
    'meta',

    'pricing_mode',
    'pricing_attribute_id',
    'pricing_field',

    'sort_order',
    'is_active',
  ];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'meta' => 'array',
    ];
  }

  public array $translatable = ['name'];

  public function pricingAttribute(): BelongsTo
  {
    return $this->belongsTo(Attribute::class, 'pricing_attribute_id');
  }

  public function family(): BelongsTo
  {
    return $this->belongsTo(ProductFamily::class, 'family_id');
  }

  public function attributes(): BelongsToMany
  {
    return $this->belongsToMany(Attribute::class, 'attribute_product_type')
      ->withPivot(['is_required', 'is_variant_only', 'sort_order'])
      ->orderBy('attribute_product_type.sort_order');
  }

  protected static function newFactory()
  {
    return \Nicole\Box\Core\Database\Factories\ProductTypeFactory::new();
  }
}
