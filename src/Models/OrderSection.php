<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasNicoleMedia;
use Spatie\MediaLibrary\HasMedia;

class OrderSection extends Model implements HasMedia
{
  use HasNicoleMedia;

  protected $fillable = [
    'order_id',
    'item_id',
    'type',
    'title',

    'price_total',
    'price_grand_total',
    'price_vat',
    'price_vat_percent',
    'price_discount',
    'price_discount_percent',

    'description',
    'estimate',
    'meta',
  ];

  protected function casts(): array
  {
    return [
      'price_total' => 'float',
      'price_grand_total' => 'float',
      'price_vat' => 'float',
      'price_vat_percent' => 'float',
      'price_discount' => 'float',
      'price_discount_percent' => 'float',
      'description' => 'array',
      'estimate' => 'array',
      'meta' => 'array',
    ];
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class, 'order_id');
  }

  /**
   * ОБНОВЛЕНО: Связь со всеми физическими товарами из каталога, относящимися к этому изделию
   */
  public function products(): HasMany
  {
    return $this->hasMany(OrderProduct::class, 'order_section_id');
  }

  /**
   * Регистрация коллекции чертежей в медиабиблиотеке
   */
  public function registerMediaCollections(): void
  {
    // Ослабляем ограничение singleFile, так как у одного изделия может быть массив чертежей
    $this->addMediaCollection('drawing');
  }
}
