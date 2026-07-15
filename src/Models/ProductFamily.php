<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductFamily extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'external_code',
    'code',
    'slug',
    'name',
    'icon',
    'meta_schema',
    'sort_order',
    'is_active',
  ];

  public array $translatable = ['name'];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'meta_schema' => 'array',
    ];
  }

  public function types(): HasMany
  {
    return $this->hasMany(ProductType::class, 'family_id');
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\ProductFamilyFactory
  {
    return \Nicole\Box\Core\Database\Factories\ProductFamilyFactory::new();
  }

  /**
   * Связь со всеми ценовыми группами этого семейства
   */
  public function priceGroups(): HasMany
  {
    return $this->hasMany(PriceGroup::class, 'product_family_id')->orderBy('sort_order');
  }

}
