<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  public const string TYPE_STRING = 'string';
  public const string TYPE_NUMERIC = 'numeric';
  public const string TYPE_BOOLEAN = 'boolean';
  public const string TYPE_DICTIONARY = 'dictionary';
  public const string TYPE_COMPLEX = 'complex_reference';

  public const string CHANNEL_WIDGET = 'widget';
  public const string CHANNEL_CATALOG = 'catalog';

  protected $fillable = [
    'external_code',
    'code',
    'type',
    'name',
    'unit_id',
    'complex_dictionary_id',
    'sort_order',
    'is_active',
    'is_multiple',
  ];

  public array $translatable = ['name'];

  protected $casts = [
    'is_active' => 'boolean',
    'is_multiple' => 'boolean',
  ];

  public function options(): HasMany
  {
    return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
  }

  public function unit(): BelongsTo
  {
    return $this->belongsTo(Unit::class);
  }

  public function complexDictionary(): BelongsTo
  {
    return $this->belongsTo(ComplexDictionary::class, 'complex_dictionary_id');
  }

  public function productTypes(): BelongsToMany
  {
    return $this->belongsToMany(
      ProductType::class,
      'attribute_product_type',
    )->withPivot(['is_required', 'is_variant_only', 'sort_order']);
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\AttributeFactory
  {
    return \Nicole\Box\Core\Database\Factories\AttributeFactory::new();
  }

}
