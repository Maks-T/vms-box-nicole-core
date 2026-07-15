<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductAttributeValue extends Model
{
  use HasFactory;

  public $timestamps = false;

  protected $fillable = [
    'attribute_id',
    'attributable_type',
    'attributable_id',
    'value_string',
    'value_numeric',
    'value_boolean',
    'value_option_id',
    'value_complex_id',
    'value_entity_id',
  ];

  protected function casts(): array
  {
    return ['value_numeric' => 'float', 'value_boolean' => 'boolean'];
  }

  public function attribute(): BelongsTo
  {
    return $this->belongsTo(Attribute::class);
  }

  public function option(): BelongsTo
  {
    return $this->belongsTo(AttributeOption::class, 'value_option_id');
  }

  public function complexRecord(): BelongsTo
  {
    return $this->belongsTo(ComplexDictionaryRecord::class, 'value_complex_id');
  }

  public function referencedEntity(): BelongsTo
  {
    return $this->belongsTo(Product::class, 'value_entity_id');
  }

  public function attributable(): MorphTo
  {
    return $this->morphTo();
  }

  /**
   * Оставляет только те опции, которые физически привязаны к переданным товарам или их вариациям
   */
  public function scopeUsedInCatalog($query, iterable $productIds, iterable $variantIds)
  {
    return $query->whereNotNull('value_option_id')
      ->where(function ($q) use ($productIds, $variantIds) {
        $q->where(function ($sub) use ($productIds) {
          $sub->where('attributable_type', (new Product)->getMorphClass())
            ->whereIn('attributable_id', $productIds);
        })->orWhere(function ($sub) use ($variantIds) {
          $sub->where('attributable_type', (new ProductVariant)->getMorphClass())
            ->whereIn('attributable_id', $variantIds);
        });
      });
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\ProductAttributeValueFactory
  {
    return \Nicole\Box\Core\Database\Factories\ProductAttributeValueFactory::new();
  }
}
