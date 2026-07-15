<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariantPrice extends Model
{
  use HasFactory;

  protected $fillable = [
    'product_variant_id',
    'price_type_id',
    'markup_percent',
    'price',
  ];

  protected function casts(): array
  {
    return [
      'markup_percent' => 'float',
      'price' => 'float',
    ];
  }

  public function variant(): BelongsTo
  {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
  }

  public function type(): BelongsTo
  {
    return $this->belongsTo(PriceType::class, 'price_type_id');
  }

  protected static function booted(): void
  {
    $refreshProductPrice = function (ProductVariantPrice $priceRecord) {
      $priceRecord->variant?->product?->refreshMinPrice();
    };

    static::saved($refreshProductPrice);
    static::deleted($refreshProductPrice);
  }

  protected static function newFactory()
  {
    return \Nicole\Box\Core\Database\Factories\ProductVariantPriceFactory::new();
  }
}
