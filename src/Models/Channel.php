<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Traits\HasExternalCode;
use Spatie\Translatable\HasTranslations;

class Channel extends Model
{
  use HasExternalCode;
  use HasTranslations;

  protected $fillable = ['external_code', 'code', 'name', 'is_active', 'sort_order']; 

  public array $translatable = ['name'];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
    ];
  }
}
