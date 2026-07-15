<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduct extends Model
{
  protected $table = 'order_products';

  protected $fillable = [
    'order_id',
    'order_section_id',
    'product_variant_id',
    'quantity',
  ];

  protected function casts(): array
  {
    return [
      'quantity' => 'float',
    ];
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class, 'order_id');
  }

  public function section(): BelongsTo
  {
    return $this->belongsTo(OrderSection::class, 'order_section_id');
  }

  public function variant(): BelongsTo
  {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
  }
}
