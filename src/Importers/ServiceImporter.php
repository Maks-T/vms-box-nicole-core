<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductAttributeValue;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Models\ProductVariantPrice;
use Nicole\Box\Core\Models\Unit;
use Nicole\Box\Core\Support\Constants\CatalogType;

class ServiceImporter implements ImportModuleInterface
{
  private array $cachedUnits = [];
  private array $cachedCategories = [];
  private array $cachedAttributes = [];

  private int $retailPriceId;
  private int $serviceTypeId;
  private int $targetAttrId;

  public function getName(): string
  {
    return 'Universal Services Matrix (From JSON)';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $servicesData = $data['services_import'] ?? [];
    if (empty($servicesData)) {
      $command->warn("  ⚠ Skipped: import_services.json not found or empty.");
      return;
    }

    $this->initBaseData($servicesData);
    $this->createCategories($servicesData['categories'] ?? []);
    $this->importServices($servicesData['services'] ?? [], $command);
  }

  private function initBaseData(array $data): void
  {
    $this->retailPriceId = PriceType::where('slug', 'retail')->value('id') ?? 1;

    $family = ProductFamily::updateOrCreate(
      ['external_code' => 'fam_service'],
      [
        'code' => 'service',
        'slug' => 'service',
        'name' => ['ru' => 'Услуги', 'en' => 'Services'],
        'is_active' => true
      ]
    );

    $serviceType = ProductType::updateOrCreate(
      ['external_code' => 'type_service'],
      [
        'family_id' => $family->id,
        'code' => 'service',
        'slug' => 'service',
        'name' => ['ru' => 'Услуги обработки', 'en' => 'Processing Services'],
        'is_active' => true
      ]
    );
    $this->serviceTypeId = $serviceType->id;

    $syncData = [];
    $sort = 10;


    foreach ($data['attributes'] ?? [] as $code => $attrDef) {
      $attr = Attribute::updateOrCreate(
        ['external_code' => "attr_{$code}"],
        [
          'code' => $code,
          'type' => $attrDef['type'],
          'name' => $attrDef['name'],
          'option_param_type' => $attrDef['option_param_type'] ?? null,
          'is_multiple' => $attrDef['is_multiple'] ?? false,
          'is_active' => true
        ]
      );

      $this->cachedAttributes[$code] = $attr;
      $syncData[$attr->id] = ['is_variant_only' => false, 'sort_order' => $sort++];
    }


    $targetAttr = Attribute::updateOrCreate(
      ['external_code' => 'attr_target_material'],
      [
        'code' => 'target_material',
        'type' => Attribute::TYPE_DICTIONARY,
        'name' => ['ru' => 'Для материала', 'en' => 'For material'],
        'is_active' => true,
        'is_multiple' => false,
        'option_param_type' => 'string'
      ]
    );

    $this->targetAttrId = $targetAttr->id;
    $syncData[$targetAttr->id] = ['is_variant_only' => true, 'sort_order' => $sort++];

    $serviceType->attributes()->syncWithoutDetaching($syncData);


    foreach ($data['services'] ?? [] as $srv) {
      if (!empty($srv['unit'])) {
        Unit::firstOrCreate([
          'slug' => $srv['unit']],
          [
            'name' => [
              'ru' => $srv['unit'],
              'en' => $srv['unit']
            ],
            'symbol' => [
              'ru' => $srv['unit'],
              'en' => $srv['unit']
            ]
          ]);
      }
    }
    $this->cachedUnits = Unit::pluck('id', 'slug')->toArray();
  }

  private function createCategories(array $categories): void
  {
    $rootServiceCat = Category::updateOrCreate(
      ['external_code' => 'cat_srv_root'],
      ['slug' => 'services', 'name' => ['ru' => 'Услуги калькулятора', 'en' => 'Calculator Services'], 'is_active' => true]
    );

    foreach ($categories as $slug => $data) {
      $cat = Category::updateOrCreate(
        ['external_code' => "cat_srv_{$slug}"],
        ['slug' => $slug, 'name' => $data['name'], 'parent_id' => $rootServiceCat->id, 'is_active' => true]
      );
      $this->cachedCategories[$slug] = $cat->id;
    }
  }

  private function importServices(array $services, Command $command): void
  {
    $bar = $command->getOutput()->createProgressBar(count($services));

    foreach ($services as $item) {
      $categoryId = $this->cachedCategories[$item['category']] ?? null;
      $unitId = $this->cachedUnits[$item['unit']] ?? null;
      if (!$categoryId) continue;

      // Создаем или обновляем базовую услугу
      $product = Product::updateOrCreate(
        ['external_code' => "prod_srv_{$item['slug']}"],
        [
          'catalog_type' => CatalogType::SERVICE,
          'product_type_id' => $this->serviceTypeId,
          'category_id' => $categoryId,
          'unit_id' => $unitId,

          'code' => $item['code'] ?? $item['slug'],

          'slug' => $item['slug'],
          'name' => $item['name'],
          'is_active' => (bool) ($item['is_active'] ?? true), // Поддержка динамической активности
        ]
      );

      ProductAttributeValue::where('attributable_id', $product->id)->where('attributable_type', $product->getMorphClass())->delete();

      foreach ($item['eav'] ?? [] as $attrCode => $values) {
        $attribute = $this->cachedAttributes[$attrCode] ?? null;
        if (!$attribute) continue;

        $valuesArray = is_array($values) ? $values : [$values];

        foreach ($valuesArray as $val) {
          $recordData = [
            'attribute_id' => $attribute->id,
            'attributable_id' => $product->id,
            'attributable_type' => $product->getMorphClass(),
          ];

          if ($attribute->type === Attribute::TYPE_DICTIONARY) {
            $opt = AttributeOption::firstOrCreate(
              ['external_code' => "opt_srv_{$attrCode}_{$val}"],
              ['attribute_id' => $attribute->id, 'slug' => $val, 'value' => ['ru' => ucfirst((string)$val), 'en' => ucfirst((string)$val)]]
            );
            $recordData['value_option_id'] = $opt->id;
          } elseif ($attribute->type === Attribute::TYPE_BOOLEAN) {
            $recordData['value_boolean'] = (bool)$val;
          } else {
            $recordData['value_string'] = (string)$val;
          }

          ProductAttributeValue::create($recordData);
        }
      }

      // Импортируем цены с поддержкой валюты и процента наценки
      foreach ($item['prices'] ?? [] as $materialCode => $priceData) {
        // Извлекаем финансовую структуру из нового формата JSON [2]
        $costPrice = (float)($priceData['cost_price'] ?? 0.0);
        $currency = $priceData['currency'] ?? 'BYN';
        $markup = (float)($priceData['markup'] ?? 0.0);

        $variant = ProductVariant::updateOrCreate(
          ['external_code' => "sku_srv_{$item['slug']}_{$materialCode}"],
          [
            'product_id' => $product->id,
            'sku' => "{$item['slug']}_{$materialCode}",
            'cost_price' => $costPrice,
            'currency' => $currency,
            'is_default' => false,
            'is_active' => true,
            'is_manual_pricing' => true,
          ]
        );

        // Сохраняем исходную наценку из боевой базы [2]
        ProductVariantPrice::updateOrCreate(
          ['product_variant_id' => $variant->id, 'price_type_id' => $this->retailPriceId],
          ['markup_percent' => $markup]
        );

        $optMaterial = AttributeOption::firstOrCreate(
          ['external_code' => "opt_target_material_{$materialCode}"],
          ['attribute_id' => $this->targetAttrId, 'slug' => $materialCode, 'value' => ['ru' => ucfirst($materialCode), 'en' => ucfirst($materialCode)]]
        );

        ProductAttributeValue::where('attributable_id', $variant->id)->where('attributable_type', $variant->getMorphClass())->delete();
        ProductAttributeValue::create([
          'attribute_id' => $this->targetAttrId,
          'attributable_id' => $variant->id,
          'attributable_type' => $variant->getMorphClass(),
          'value_option_id' => $optMaterial->id,
        ]);
      }

      $product->refreshMinPrice();
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
