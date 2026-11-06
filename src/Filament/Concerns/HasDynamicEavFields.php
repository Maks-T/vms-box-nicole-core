<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Filament\Concerns;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

trait HasDynamicEavFields
{
  public static function getDynamicEavSchema(
    ?int    $productTypeId,
    ?string $entityType = null,
  ): array
  {

    $entityType = $entityType ?? new Product()->getMorphClass();

    if (!$productTypeId) {
      return [
        TextEntry::make('hint')
          ->hiddenLabel()
          ->state(
            __('Please select a type to view technical specifications.'),
          ),
      ];
    }

    $productType = static::loadEavProductType($productTypeId);

    if (!$productType) {
      return [];
    }

    // @since 2026-11-06: все записи сложных справочников для этого типа - одним запросом
    $complexOptions = static::loadComplexOptions($productType);

    $fields = [];
    foreach ($productType->attributes as $attribute) {
      $isVariantOnly = (bool)($attribute->pivot->is_variant_only ?? false);

      if ($entityType === new Product()->getMorphClass() && $isVariantOnly) {
        continue;
      }
      if ($entityType === new ProductVariant()->getMorphClass() && !$isVariantOnly) {
        continue;
      }

      $field = match ($attribute->type) {
        Attribute::TYPE_BOOLEAN => Toggle::make("eav.{$attribute->id}")->inline(
          false,
        ),

        Attribute::TYPE_NUMERIC => TextInput::make("eav.{$attribute->id}")
          ->numeric()
          ->suffix($attribute->unit?->symbol ?? ''),

        Attribute::TYPE_DICTIONARY => Select::make("eav.{$attribute->id}")
          ->options($attribute->options->pluck('value', 'id'))
          ->searchable()
          ->preload()
          ->multiple((bool)$attribute->is_multiple),

        Attribute::TYPE_COMPLEX => Select::make("eav.{$attribute->id}")
          ->options($complexOptions[$attribute->complex_dictionary_id] ?? [])
          ->searchable()
          ->preload()
          ->multiple((bool)$attribute->is_multiple),

        default => TextInput::make("eav.{$attribute->id}"),
      };

      $fields[] = $field->label($attribute->name)->columnSpan(1);
    }

    if (empty($fields)) {
      return [
        TextEntry::make('no_fields')
          ->hiddenLabel()
          ->state(__('No specific attributes for this context.')),
      ];
    }

    return [
      Grid::make([
        'default' => 1,
        'md' => 2,
        'lg' => 3,
      ])
        ->schema($fields)
        ->columnSpanFull(),
    ];
  }

  /**
   * Кэш на время HTTP-запроса: схема вкладки «Характеристики» вычисляется Filament
   * многократно (рендер, валидация, каждый live-апдейт).
   *
   * @var array<int, ProductType|null>
   * @since 2026-11-06
   */
  private static array $eavProductTypeCache = [];

  /**
   * @var array<int, array<int|string, array<int|string, string>>>
   * @since 2026-11-06
   */
  private static array $eavComplexOptionsCache = [];

  /**
   * @since 2026-11-06
   */
  protected static function loadEavProductType(int $productTypeId): ?ProductType
  {
    if (!array_key_exists($productTypeId, self::$eavProductTypeCache)) {
      self::$eavProductTypeCache[$productTypeId] = ProductType::with([
        'attributes' => function ($query) {
          $query->orderBy('attribute_product_type.sort_order');
        },
        'attributes.unit',
        'attributes.options',
      ])->find($productTypeId);
    }

    return self::$eavProductTypeCache[$productTypeId];
  }

  /**
   * @return array<int|string, array<int|string, string>> dictionary_id => [record_id => name]
   * @since 2026-11-06
   */
  protected static function loadComplexOptions(ProductType $productType): array
  {
    $typeId = (int)$productType->getKey();

    if (!array_key_exists($typeId, self::$eavComplexOptionsCache)) {
      $dictionaryIds = $productType->attributes
        ->where('type', Attribute::TYPE_COMPLEX)
        ->pluck('complex_dictionary_id')
        ->filter()
        ->unique()
        ->values();

      self::$eavComplexOptionsCache[$typeId] = $dictionaryIds->isEmpty()
        ? []
        : ComplexDictionaryRecord::query()
          ->whereIn('dictionary_id', $dictionaryIds)
          ->where('is_active', true)
          ->get(['id', 'dictionary_id', 'name'])
          ->groupBy('dictionary_id')
          ->map(fn($records) => $records->pluck('name', 'id')->all())
          ->all();
    }

    return self::$eavComplexOptionsCache[$typeId];
  }

  protected function loadEavData(Model $record, array &$data): void
  {
    $values = ProductAttributeValue::where('attributable_id', $record->id)
      ->where('attributable_type', $record->getMorphClass())
      ->get();

    $attributeIds = $values->pluck('attribute_id')->unique();
    $attributes = Attribute::whereIn('id', $attributeIds)->get()->keyBy('id');

    if (!isset($data['eav'])) {
      $data['eav'] = [];
    }

    foreach ($values as $val) {
      $attr = $attributes->get($val->attribute_id);
      if (!$attr) continue;

      $parsedValue =
        $val->value_option_id ??
        ($val->value_complex_id ??
          ($val->value_numeric ?? ($val->value_boolean ?? $val->value_string)));


      if ($attr->is_multiple) {
        if (!isset($data['eav'][$val->attribute_id])) {
          $data['eav'][$val->attribute_id] = [];
        }
        $data['eav'][$val->attribute_id][] = $parsedValue;
      } else {
        $data['eav'][$val->attribute_id] = $parsedValue;
      }
    }
  }

  protected function saveEavData(Model $record, array $eavData): void
  {
    if (empty($eavData)) {
      return;
    }

    $attributes = Attribute::whereIn('id', array_keys($eavData))
      ->get()
      ->keyBy('id');

    foreach ($eavData as $attributeId => $value) {
      $attribute = $attributes->get($attributeId);
      if (!$attribute) {
        continue;
      }

      $isComplex = $attribute->type === Attribute::TYPE_COMPLEX;
      $isOption = $attribute->type === Attribute::TYPE_DICTIONARY;


      ProductAttributeValue::where([
        'attribute_id' => $attributeId,
        'attributable_id' => $record->id,
        'attributable_type' => $record->getMorphClass(),
      ])->delete();


      if ($value === null || $value === '' || $value === []) {
        continue;
      }


      $valuesToSave = is_array($value) ? $value : [$value];


      foreach ($valuesToSave as $singleValue) {
        ProductAttributeValue::create([
          'attribute_id' => $attributeId,
          'attributable_id' => $record->id,
          'attributable_type' => $record->getMorphClass(),
          'value_string' => is_string($singleValue) && !$isComplex && !$isOption ? $singleValue : null,
          'value_numeric' => is_numeric($singleValue) && !$isComplex && !$isOption ? (float)$singleValue : null,
          'value_boolean' => is_bool($singleValue) ? $singleValue : null,
          'value_option_id' => $isOption ? (int)$singleValue : null,
          'value_complex_id' => $isComplex ? (int)$singleValue : null,
        ]);
      }

    }
  }
}
