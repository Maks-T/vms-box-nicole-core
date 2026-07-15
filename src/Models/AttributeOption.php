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
    'extra_value',
    'sort_order',
  ];

  public array $translatable = ['value'];

  protected function casts(): array
  {
    return [
      'meta' => 'array',
      'extra_value' => 'float',
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
}
