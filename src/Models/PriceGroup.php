<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceGroup extends Model
{
  use HasFactory;
  use HasSettings;
  use HasTranslations;
  use HasExternalCode;

  protected $fillable = [
    'external_code',
    'product_family_id',
    'slug',
    'name',
    'description',
    'meta',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name',  'description'];

  protected function casts(): array
  {
    return [
      'meta' => 'array',
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  /**
   * Связь с семейством товаров
   */
  public function family(): BelongsTo
  {
    return $this->belongsTo(ProductFamily::class, 'product_family_id');
  }

  /**
   * Связь со всеми привязанными модификациями товаров (SKU)
   */
  public function variants(): HasMany
  {
    return $this->hasMany(ProductVariant::class, 'price_group_id');
  }

}
