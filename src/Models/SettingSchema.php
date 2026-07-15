<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;

class SettingSchema extends Model
{
  protected $table = 'setting_schemas';

  protected $fillable = [
    'entity_type',
    'meta_schema' 
  ];

  protected function casts(): array
  {
    return [
      'meta_schema' => 'array' 
    ];
  }
}
