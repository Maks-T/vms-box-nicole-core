<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasGlobalDefault;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceType extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasGlobalDefault;
  use HasFactory;

  protected $fillable = [
    'external_code',
    'slug',
    'name',
    'description',
    'is_default',
    'currency_id',
    'sort_order',
  ];

  public array $translatable = ['name', 'description'];

  public function currency(): BelongsTo
  {
    return $this->belongsTo(Currency::class);
  }

  protected static function booted(): void
  {
    // трейт HasGlobalDefault все делает сам
  }

  protected static function newFactory()
  {
    return \Nicole\Box\Core\Database\Factories\PriceTypeFactory::new();
  }

}
