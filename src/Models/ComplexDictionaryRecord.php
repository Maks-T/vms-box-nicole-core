<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nicole\Box\Core\Traits\HasExternalCode;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplexDictionaryRecord extends Model
{
  use HasExternalCode;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'dictionary_id',
    'external_code',
    'slug',
    'name',
    'meta',
    'sort_order',
    'is_active',
  ];

  public array $translatable = ['name'];

  protected function casts(): array
  {
    return [
      'meta' => 'array',
      'sort_order' => 'integer',
      'is_active' => 'boolean',
    ];
  }

  public function dictionary(): BelongsTo
  {
    return $this->belongsTo(ComplexDictionary::class, 'dictionary_id');
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\ComplexDictionaryRecordFactory
  {
    return \Nicole\Box\Core\Database\Factories\ComplexDictionaryRecordFactory::new();
  }

}
