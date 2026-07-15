<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
  use HasExternalCode;
  use HasSettings;
  use HasTranslations;
  use NodeTrait;
  use HasFactory;

  protected $fillable = [
    'parent_id',
    'external_code',
    'name',
    'slug',
    'description',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name', 'description'];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
    ];
  }

  public function getFullPathAttribute(): string
  {
    return $this->ancestors->pluck('name')->push($this->name)->implode(' > ');
  }

  public function products(): HasMany
  {
    return $this->hasMany(Product::class);
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\CategoryFactory
  {
    return \Nicole\Box\Core\Database\Factories\CategoryFactory::new();
  }

}
