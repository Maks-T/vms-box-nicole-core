<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplexDictionary extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'external_code',
    'code',
    'name',
    'meta_schema',
    'sort_order',
    'is_active',
  ];

  public array $translatable = ['name'];

  protected function casts(): array
  {
    return [
      'meta_schema' => 'array',
      'is_active' => 'boolean',
    ];
  }

  public function records(): HasMany
  {
    return $this->hasMany(
      ComplexDictionaryRecord::class,
      'dictionary_id',
    )->orderBy('sort_order');
  }

  protected static function booted(): void
  {
    static::updated(function (ComplexDictionary $dictionary) {
      if ($dictionary->wasChanged('meta_schema')) {
        $oldSchema = $dictionary->getOriginal('meta_schema') ?? [];
        $newSchema = $dictionary->meta_schema ?? [];

        $oldKeys = array_column($oldSchema, 'key');
        $newKeys = array_column($newSchema, 'key');

        $deletedKeys = array_diff($oldKeys, $newKeys);

        if (! empty($deletedKeys)) {
          foreach ($dictionary->records as $record) {
            $meta = $record->meta ?? [];
            $hasChanges = false;

            foreach ($deletedKeys as $key) {
              if (array_key_exists($key, $meta)) {
                unset($meta[$key]);
                $hasChanges = true;
              }
            }

            if ($hasChanges) {
              $record->updateQuietly(['meta' => $meta]);
            }
          }
        }
      }
    });
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\ComplexDictionaryFactory
  {
    return \Nicole\Box\Core\Database\Factories\ComplexDictionaryFactory::new();
  }

}
