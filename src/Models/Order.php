<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Illuminate\Support\Str;

class Order extends Model
{
  use HasExternalCode;

  protected $fillable = [
    'code',
    'external_code',
    'customer_id',
    'grand_total',
    'currency',
    'locale',
    'status_id',
    'customer_comment',
    'manager_comment',
    'calc_state', // Переименовано в calc_state (маппинг 1-к-1)
    'manager_id',
  ];

  protected function casts(): array
  {
    return [
      'grand_total' => 'float',
      'calc_state' => 'array', // Переименовано в calc_state (маппинг 1-к-1)
    ];
  }

  public function customer(): BelongsTo
  {
    return $this->belongsTo(Customer::class, 'customer_id');
  }

  public function status(): BelongsTo
  {
    return $this->belongsTo(OrderStatus::class, 'status_id');
  }

  /**
   * Связь со всеми секциями (изделиями) в рамках этого заказа
   */
  public function sections(): HasMany
  {
    return $this->hasMany(OrderSection::class, 'order_id');
  }

  /**
   * ОБНОВЛЕНО: Связь со всеми связанными товарами этого заказа (order_products вместо order_items)
   */
  public function products(): HasMany
  {
    return $this->hasMany(OrderProduct::class, 'order_id');
  }

  public function manager(): BelongsTo
  {
    $userModel = config('nicole.models.staff', \App\Models\User::class);
    return $this->belongsTo($userModel, 'manager_id');
  }

  protected static function booted(): void
  {
    static::creating(function (Order $order) {
      if (empty($order->code)) {
        $prefix = env('VMS_ORDER_PREFIX', 'O');
        $year = date('y');
        $sequence = self::count() + 1;

        do {
          $suffix = strtoupper(Str::random(4));
          $code = "{$prefix}-{$year}{$sequence}-{$suffix}";
        } while (self::where('code', $code)->exists());

        $order->code = $code;
      }
    });
  }

  public function getKpNumberAttribute(): string
  {
    return $this->code;
  }
}
