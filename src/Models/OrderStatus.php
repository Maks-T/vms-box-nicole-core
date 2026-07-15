<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasGlobalDefault;
use Spatie\Translatable\HasTranslations;

class OrderStatus extends Model
{
  use HasTranslations;
  use HasGlobalDefault;

  protected $fillable = [
    'slug',
    'name',
    'color',
    'is_default',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name'];

  protected function casts(): array
  {
    return [
      'is_default' => 'boolean',
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  public function orders(): HasMany
  {
    return $this->hasMany(Order::class, 'status_id');
  }
}
