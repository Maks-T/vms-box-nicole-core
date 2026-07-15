<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;

class Warehouse extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'external_code',
    'slug',
    'name',
    'description',
    'address',
    'latitude',
    'longitude',
    'schedule',
    'phone',
    'email',
    'is_pickup_point',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name', 'description'];

  protected function casts(): array
  {
    return [
      'schedule' => 'array',
      'latitude' => 'float',
      'longitude' => 'float',
      'is_pickup_point' => 'boolean',
      'is_active' => 'boolean',
    ];
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\WarehouseFactory
  {
    return \Nicole\Box\Core\Database\Factories\WarehouseFactory::new();
  }

}
