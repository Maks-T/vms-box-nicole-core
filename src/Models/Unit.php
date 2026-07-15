<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = ['slug', 'code', 'name', 'symbol', 'sort_order'];

  public array $translatable = ['name', 'symbol'];

  protected static function newFactory()
  {
    return \Nicole\Box\Core\Database\Factories\UnitFactory::new();
  }
}
