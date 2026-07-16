<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nicole\Box\Core\Support\Constants\MediaCollection;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasNicoleMedia;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeOption extends Model implements HasMedia
{
  use HasFactory;

  use HasExternalCode;
  use HasNicoleMedia;
  use HasSettings;
  use HasTranslations;

  protected $fillable = [
    'attribute_id',
    'slug',
    'external_code',
    'value',
    'meta',
    'param',
    'sort_order',
  ];

  public array $translatable = ['value'];

  protected function casts(): array
  {
    return [
      'meta' => 'array',
      'sort_order' => 'integer',
    ];
  }

  public function attribute(): BelongsTo
  {
    return $this->belongsTo(Attribute::class);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection(MediaCollection::MAIN)->singleFile();
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\AttributeOptionFactory
  {
    return \Nicole\Box\Core\Database\Factories\AttributeOptionFactory::new();
  }

  /**
   * Динамический аксессор для автоматического приведения типа технического параметра.
   * На фронтенд и в API всегда будет уходить правильный тип данных (float, boolean или string).
   */
  public function getParamAttribute($value)
  {
    if ($value === null) {
      return null;
    }

    // Получаем тип из родительского атрибута (если связь не загружена, по дефолту считаем строкой)
    $type = $this->attribute?->option_param_type ?? 'string';

    return match ($type) {
      'numeric' => (float)$value,
      'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
      default => (string)$value,
    };
  }

}
