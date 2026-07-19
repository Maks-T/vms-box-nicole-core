<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\Unit;
use Nicole\Box\Core\Support\Constants\CatalogType;
use Nicole\Box\Core\Support\Constants\MediaCollection;

class ProductImporter implements ImportModuleInterface
{
  private array $mapTypes = [];
  private array $mapCategories = [];
  private array $mapAttributes = [];
  private array $mapOptions = [];
  private array $mapComplexRecords = [];
  private array $mapPriceGroups = [];
  private array $mapUnits = [];

  public function getName(): string
  {
    return 'Products, Variants & EAV';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $retailPriceId = \Nicole\Box\Core\Models\PriceType::where('slug', 'retail')->value('id') ?? 1;

    $mainWarehouse = \Nicole\Box\Core\Models\Warehouse::firstOrCreate(
      ['external_code' => 'wh_main'],
      [
        'slug' => 'main',
        'name' => ['ru' => 'Главный склад', 'en' => 'Main Warehouse'],
        'is_active' => true,
      ]
    );

    $products = $data['products'] ?? [];
    if (empty($products)) {
      return;
    }

    $this->warmUpCache();
    $bar = $command->getOutput()->createProgressBar(count($products));

    foreach ($products as $item) {
      $typeId = $this->mapTypes[$item['product_type_external_code'] ?? ''] ?? null;
      $categoryId = $this->mapCategories[$item['category_external_code'] ?? ''] ?? null;

      $unitCode = $item['unit_code'] ?? 'pcs';
      if (!isset($this->mapUnits[$unitCode])) {
        $unitName = match($unitCode) {
          'm' => ['ru' => 'м.п.', 'en' => 'rm'],
          'set' => ['ru' => 'компл.', 'en' => 'set'],
          'srv' => ['ru' => 'усл.', 'en' => 'srv'],
          'km' => ['ru' => 'км', 'en' => 'km'],
          'floor' => ['ru' => 'этаж', 'en' => 'floor'],
          default => ['ru' => 'Шт.', 'en' => 'Pcs'],
        };
        $newUnit = Unit::firstOrCreate(
          ['slug' => $unitCode],
          ['name' => $unitName, 'symbol' => $unitName, 'code' => $unitCode, 'is_active' => true]
        );
        $this->mapUnits[$unitCode] = $newUnit->id;
      }
      $unitId = $this->mapUnits[$unitCode];

      // Создаем или обновляем продукт
      $product = Product::updateOrCreate(
        ['external_code' => $item['external_code']],
        [
          'product_type_id' => $typeId,
          'category_id' => $categoryId,
          'catalog_type' => $item['catalog_type'] ?? CatalogType::PRODUCT,
          'unit_id' => $unitId,

          'code' => $item['code'] ?? $item['slug'],

          'slug' => $item['slug'],
          'name' => $item['name'],
          'is_active' => (bool) ($item['is_active'] ?? true)
        ]
      );

      // Прикрепляем медиафайлы и EAV-характеристики
      $this->attachMedia($product, $item['preview_picture'] ?? null, $item['detail_picture'] ?? null, $command);
      $this->saveEav($product, $item['eav'] ?? []);

      // динамическая генерация анонса из характеристик (если отсутствует в файле импорта)
      if (empty($item['short_description'])) {
        $shortDesc = [];
        $locales = config('nicole.locales', ['ru', 'en']);

        // получаем сохраненные EAV-характеристики этого продукта
        $eavValues = ProductAttributeValue::where('attributable_id', $product->id)
          ->where('attributable_type', $product->getMorphClass())
          ->with(['attribute.unit', 'option', 'complexRecord'])
          ->get();

        foreach ($locales as $locale) {
          $specs = [];

          foreach ($eavValues as $val) {
            $attrName = $val->attribute->getTranslation('name', $locale);
            $valString = null;

            // Вычисляем текстовое значение в зависимости от типа EAV-атрибута
            if ($val->option) {
              $valString = $val->option->getTranslation('value', $locale);
            } elseif ($val->complexRecord) {
              $valString = $val->complexRecord->getTranslation('name', $locale);
            } elseif ($val->value_boolean !== null) {
              $valString = $val->value_boolean
                ? ($locale === 'ru' ? 'Да' : 'Yes')
                : ($locale === 'ru' ? 'Нет' : 'No');
            } elseif ($val->value_numeric !== null) {
              $valString = (string)(float)$val->value_numeric;

              if ($val->attribute->unit) {
                $unitSymbol = $val->attribute->unit->getTranslation('symbol', $locale);
                if ($unitSymbol) {
                  $valString .= ' ' . $unitSymbol;
                }
              }
            } else {
              $valString = $val->value_string;
            }

            if (!empty($attrName) && !empty($valString)) {
              $specs[] = "{$attrName}: {$valString}";
            }
          }

          $shortDesc[$locale] = implode(', ', $specs);
        }

        // Обновляем напрямую, минуя триггеры observers
        $product->updateQuietly(['short_description' => $shortDesc]);
      }

      // Импортируем модификации (SKU) товара
      foreach ($item['variants'] ?? [] as $vData) {
        $isManualPricing = $vData['is_manual_pricing'] ?? isset($vData['price']);
        $priceGroupId = $this->mapPriceGroups[$vData['price_group_external_code'] ?? ''] ?? null;

        $variant = ProductVariant::updateOrCreate(
          ['external_code' => $vData['external_code']],
          [
            'product_id' => $product->id,
            'price_group_id' => $priceGroupId,
            'sku' => $vData['sku'],
            'name' => $vData['name'] ?? null,
            'cost_price' => $vData['cost_price'] ?? 0,
            'currency' => $vData['currency'] ?? 'RUB',
            'is_default' => $vData['is_default'] ?? false,
            'is_active' => (bool) ($vData['currency'] ?? true),
            'is_manual_pricing' => (bool) $isManualPricing,
          ]
        );

        if (isset($vData['price'])) {
          $price = (float) $vData['price'];
          $costPrice = (float) ($vData['cost_price'] ?? 0);

          if ($costPrice > 0) {
            $markup = (($price / $costPrice) - 1) * 100;
          } else {
            $variant->updateQuietly(['cost_price' => $price, 'is_manual_pricing' => true]);
            $markup = 0.0;
          }

          \Nicole\Box\Core\Models\ProductVariantPrice::updateOrCreate(
            ['product_variant_id' => $variant->id, 'price_type_id' => $retailPriceId],
            ['markup_percent' => (float) $markup]
          );
        }

        $this->attachMedia($variant, $vData['preview_picture'] ?? null, $vData['detail_picture'] ?? null, $command);
        $this->saveEav($variant, $vData['eav'] ?? []);

        $stockQty = (float) ($vData['stock'] ?? 0);
        if ($stockQty > 0) {
          \Nicole\Box\Core\Models\Stock::updateOrCreate(
            ['product_variant_id' => $variant->id, 'warehouse_id' => $mainWarehouse->id],
            ['quantity' => $stockQty, 'reserved' => 0]
          );
        }
      }

      $product->refreshMinPrice();
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }

  private function saveEav($model, array $eavData): void
  {
    ProductAttributeValue::where('attributable_id', $model->id)
      ->where('attributable_type', $model->getMorphClass())
      ->delete();

    foreach ($eavData as $attrCode => $valueOrValues) {
      /** @var Attribute $attribute */
      $attribute = $this->mapAttributes[$attrCode] ?? null;
      if (!$attribute) {
        continue;
      }

      $values = is_array($valueOrValues) ? $valueOrValues : [$valueOrValues];

      foreach ($values as $value) {
        if ($value === null || $value === '') {
          continue;
        }

        $recordData = [
          'attribute_id' => $attribute->id,
          'attributable_id' => $model->id,
          'attributable_type' => $model->getMorphClass(),
          'value_string' => null,
          'value_numeric' => null,
          'value_boolean' => null,
          'value_option_id' => null,
          'value_complex_id' => null,
        ];

        if ($attribute->type === Attribute::TYPE_DICTIONARY) {
          $recordData['value_option_id'] = $this->mapOptions[$value] ?? null;
        } elseif ($attribute->type === Attribute::TYPE_COMPLEX) {
          $recordData['value_complex_id'] = $this->mapComplexRecords[$value] ?? null;
        } elseif ($attribute->type === Attribute::TYPE_BOOLEAN) {
          $recordData['value_boolean'] = (bool) $value;
        } elseif ($attribute->type === Attribute::TYPE_NUMERIC) {
          $recordData['value_numeric'] = (float) $value;
        } else {
          $recordData['value_string'] = (string) $value;
        }

        if (array_filter(array_slice($recordData, 3)) !== []) {
          ProductAttributeValue::create($recordData);
        }
      }
    }
  }

  private function attachMedia($model, ?string $previewPath, ?string $detailPath, Command $command): void
  {
    $baseDir = base_path('import/export_images/');

    if ($previewPath) {
      $fullPath = $baseDir . ltrim($previewPath, '/');
      if (File::exists($fullPath)) {
        $existingMedia = $model->getFirstMedia(MediaCollection::PREVIEW);
        $fileName = basename($fullPath);

        if (!$existingMedia || $existingMedia->file_name !== $fileName) {
          $model->clearMediaCollection(MediaCollection::PREVIEW);
          $model->addMedia($fullPath)
            ->preservingOriginal()
            ->withCustomProperties(['skip_conversions' => true])
            ->toMediaCollection(MediaCollection::PREVIEW);
        }
      } else {
        $command->warn("\n⚠ Товар/SKU {$model->external_code}: Превью не найдено -> {$fullPath}");
      }
    }

    if ($detailPath) {
      $fullPath = $baseDir . ltrim($detailPath, '/');
      if (File::exists($fullPath)) {
        $existingMedia = $model->getFirstMedia(MediaCollection::MAIN);
        $fileName = basename($fullPath);

        if (!$existingMedia || $existingMedia->file_name !== $fileName) {
          $model->clearMediaCollection(MediaCollection::MAIN);
          $media = $model->addMedia($fullPath)->preservingOriginal();

          if ($previewPath) {
            $media->withCustomProperties(['skip_conversions' => true]);
          }

          $media->toMediaCollection(MediaCollection::MAIN);
        }
      } else {
        $command->warn("\n⚠ Товар/SKU {$model->external_code}: Основное фото не найдено -> {$fullPath}");
      }
    }
  }

  private function warmUpCache(): void
  {
    $this->mapTypes = ProductType::pluck('id', 'external_code')->toArray();
    $this->mapCategories = Category::pluck('id', 'external_code')->toArray();
    $this->mapOptions = AttributeOption::pluck('id', 'external_code')->toArray();
    $this->mapComplexRecords = ComplexDictionaryRecord::pluck('id', 'external_code')->toArray();
    $this->mapPriceGroups = PriceGroup::pluck('id', 'external_code')->toArray();
    $this->mapUnits = Unit::pluck('id', 'slug')->toArray();
    $this->mapAttributes = Attribute::all()->keyBy('code')->all();
  }

}
