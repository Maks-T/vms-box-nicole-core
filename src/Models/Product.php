<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nicole\Box\Core\Support\Constants\MediaCollection;
use Nicole\Box\Core\Traits\HasExternalCode;
use Nicole\Box\Core\Traits\HasNicoleMedia;
use Nicole\Box\Core\Traits\HasSettings;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;

/**
 * Класс модели базового товара.
 *
 * @method static \Illuminate\Database\Eloquent\Builder publicInChannel(string $channelCode)
 * @method static \Illuminate\Database\Eloquent\Builder filterByEav(array $filters)
 * @method static \Illuminate\Database\Eloquent\Builder search(?string $search)
 */
class Product extends Model implements HasMedia
{
  use HasExternalCode;
  use HasNicoleMedia;
  use HasSettings;
  use HasTranslations;
  use HasFactory;

  protected $fillable = [
    'catalog_type',
    'external_code',
    'code',
    'product_type_id',
    'category_id',
    'name',
    'slug',
    'unit_id',
    'short_description',
    'description',
    'min_price',
    'is_active',
    'sort_order',
  ];

  public array $translatable = ['name', 'description', 'short_description'];

  protected function casts(): array
  {
    return [
      'min_price' => 'float',
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  public function type(): BelongsTo
  {
    return $this->belongsTo(ProductType::class, 'product_type_id');
  }

  public function category(): BelongsTo
  {
    return $this->belongsTo(Category::class);
  }

  public function variants(): HasMany
  {
    return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
  }

  public function attributeValues(): MorphMany
  {
    return $this->morphMany(ProductAttributeValue::class, 'attributable');
  }

  public function unit(): BelongsTo
  {
    return $this->belongsTo(Unit::class);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection(MediaCollection::MAIN)->singleFile();
    $this->addMediaCollection(MediaCollection::PREVIEW)->singleFile();
  }

  public function linkedItems(): MorphMany
  {
    return $this->morphMany(BindingRule::class, 'parent')->orderBy(
      'sort_order',
    );
  }

  public function refreshMinPrice(): void
  {
    $pricingManager = app(\Nicole\Box\Core\Services\PricingManager::class);
    $minPrice = null;

    foreach ($this->variants()->where('is_active', true)->get() as $variant) {
      $price = $pricingManager->getVariantPrice($variant);

      if ($price > 0 && ($minPrice === null || $price < $minPrice)) {
        $minPrice = $price;
      }
    }

    $finalPrice = (float) ($minPrice ?? 0.0);

    // Обновляем значение напрямую в базе данных, минуя проверки Eloquent
    $this->newQuery()->where($this->getKeyName(), $this->getKey())->update([
      'min_price' => $finalPrice,
    ]);

    // Синхронизируем состояние текущего объекта в памяти
    $this->setAttribute('min_price', $finalPrice);
    $this->syncOriginalAttribute('min_price');
  }

  /**
   * Динамическая EAV фильтрация для каталога
   */
  public function scopeFilterByEav($query, array $filters)
  {
    foreach ($filters as $attrCode => $value) {
      if (blank($value)) continue;

      $valuesArray = explode(',', (string) $value);
      $numericValues = array_filter($valuesArray, 'is_numeric');


      $applyEavCondition = function ($sub) use ($attrCode, $valuesArray, $numericValues) {
        $sub->whereHas('attribute', fn ($a) => $a->where('code', $attrCode))
          ->where(function ($vQ) use ($valuesArray, $numericValues) {
            $vQ->whereIn('value_string', $valuesArray)
              ->orWhereHas('option', fn ($opt) => $opt->whereIn('slug', $valuesArray))
              ->orWhereHas('complexRecord', fn ($comp) => $comp->whereIn('slug', $valuesArray));

            if (!empty($numericValues)) {
              $vQ->orWhereIn('value_numeric', $numericValues);
            }
          });
      };


      $query->where(function ($q) use ($applyEavCondition) {
        // Ищем в атрибутах самого товара
        $q->whereHas('attributeValues', $applyEavCondition)
          // Или ищем в атрибутах его модификаций
          ->orWhereHas('variants', function ($vSub) use ($applyEavCondition) {
            $vSub->where('is_active', true)
              ->whereHas('attributeValues', $applyEavCondition);
          });
      });
    }

    return $query;
  }

  /**
   * Виртуальное свойство: Минимальная цена товара
   * Использование: $product->retail_price
   */
  protected function retailPrice(): EloquentAttribute
  {
    return EloquentAttribute::make(
      get: fn () => app(\Nicole\Box\Core\Services\PricingManager::class)->getRetailPrice($this),
    );
  }

  /**
   * Сквозной полнотекстовый регистронезависимый поиск по всем языковым версиям,
   * артикулам (SKU), кодам и EAV-характеристикам.
   */
  public function scopeSearch($query, ?string $search)
  {
    if (blank($search)) {
      return $query;
    }

    $searchTerm = '%' . trim($search) . '%';
    $locales = config('nicole.locales', ['ru', 'en']);

    return $query->where(function ($sub) use ($searchTerm, $locales) {

      // Поиск по полям самого товара (name, short_description, description)
      foreach ($locales as $locale) {
        $sub->orWhereRaw('name->>? ILIKE ?', [$locale, $searchTerm])
          ->orWhereRaw('short_description->>? ILIKE ?', [$locale, $searchTerm])
          ->orWhereRaw('description->>? ILIKE ?', [$locale, $searchTerm]);
      }

      // Системные коды товара
      $sub->orWhere('code', 'ILIKE', $searchTerm)
        ->orWhere('slug', 'ILIKE', $searchTerm)
        ->orWhere('external_code', 'ILIKE', $searchTerm);

      // Артикулы (SKU) и названия вариантов товара
      $sub->orWhereHas('variants', function ($vQ) use ($searchTerm, $locales) {
        $vQ->where('sku', 'ILIKE', $searchTerm)
          ->orWhere('external_code', 'ILIKE', $searchTerm);

        foreach ($locales as $locale) {
          $vQ->orWhereRaw('name->>? ILIKE ?', [$locale, $searchTerm]);
        }
      });

      // Значения EAV-атрибутов самого товара (строки + наименования опций в справочниках)
      $sub->orWhereHas('attributeValues', function ($aQ) use ($searchTerm, $locales) {
        $aQ->where('value_string', 'ILIKE', $searchTerm)
          ->orWhereHas('option', function ($oQ) use ($searchTerm, $locales) {
            $oQ->where('slug', 'ILIKE', $searchTerm);
            foreach ($locales as $locale) {
              $oQ->orWhereRaw('value->>? ILIKE ?', [$locale, $searchTerm]);
            }
          })
          ->orWhereHas('complexRecord', function ($cQ) use ($searchTerm, $locales) {
            $cQ->where('slug', 'ILIKE', $searchTerm);
            foreach ($locales as $locale) {
              $cQ->orWhereRaw('name->>? ILIKE ?', [$locale, $searchTerm]);
            }
          });
      });

      // Значения EAV-атрибутов вариантов товара
      $sub->orWhereHas('variants.attributeValues', function ($vaQ) use ($searchTerm, $locales) {
        $vaQ->where('value_string', 'ILIKE', $searchTerm)
          ->orWhereHas('option', function ($oQ) use ($searchTerm, $locales) {
            $oQ->where('slug', 'ILIKE', $searchTerm);
            foreach ($locales as $locale) {
              $oQ->orWhereRaw('value->>? ILIKE ?', [$locale, $searchTerm]);
            }
          })
          ->orWhereHas('complexRecord', function ($cQ) use ($searchTerm, $locales) {
            $cQ->where('slug', 'ILIKE', $searchTerm);
            foreach ($locales as $locale) {
              $cQ->orWhereRaw('name->>? ILIKE ?', [$locale, $searchTerm]);
            }
          });
      });

    });
  }

  protected static function newFactory(): \Nicole\Box\Core\Database\Factories\ProductFactory
  {
    return \Nicole\Box\Core\Database\Factories\ProductFactory::new();
  }

}
