<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nicole\Box\Core\Support\Constants\CatalogType;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\SettingSchema;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\Pipeline;
use Nicole\Box\Core\Models\PipelineScenario;
use Nicole\Box\Core\Models\BindingRule;

class ExportCatalogCommand extends Command
{
  protected $signature = 'vms:export
                            {--settings=import/export_settings.json : Путь для сохранения настроек}
                            {--data=import/export_data.json : Путь для сохранения данных каталога}
                            {--services=import/export_services.json : Путь для сохранения услуг}
                            {--only=* : Выборочный экспорт секций (через запятую или отдельными флагами)}
                            {--exclude=* : Исключить секции из экспорта}
                            {--no-descriptions : Не экспортировать описания (description, short_description)}';

  protected $description = 'Экспорт данных каталога и настроек в JSON файлы';

  public function handle(): int
  {
    $this->info('Начало экспорта VMS Platform...');
    if ($this->shouldExport('settings') || $this->shouldExport('channels') || $this->shouldExport('setting_schemas')) {
      $this->exportSettings();
    }
    $this->exportData();
    $this->info(PHP_EOL . 'Экспорт успешно завершен!');
    return self::SUCCESS;
  }

  protected function exportSettings(): void
  {
    $path = base_path($this->option('settings'));
    $this->line("Экспорт настроек в $path...");

    $settings = [
      'channels' => Channel::all()->mapWithKeys(fn($c) => [$c->code => ['is_public_default' => true]])->toArray(),
      'setting_schemas' => SettingSchema::all()->pluck('meta_schema', 'entity_type')->toArray(),
    ];

    if (empty($settings['channels'])) {
      $settings['channels'] = ['widget' => ['is_public_default' => true]];
    }

    $this->saveJson($path, $settings);
  }

  protected function exportData(): void
  {
    $path = base_path($this->option('data'));
    $this->line("Экспорт данных каталога в $path...");
    if ($this->option('no-descriptions')) {
      $this->warn('  * Режим --no-descriptions активен: текстовые описания будут пропущены.');
    }

    $data = [];

    if ($this->shouldExport('currencies')) {
      $data['currencies'] = Currency::all()->map(fn($c) => [
        'external_code' => $c->external_code,
        'code' => $c->code,
        'symbol' => $c->symbol,
        'symbol_native' => $c->getTranslations('symbol_native'),
        'name' => $c->getTranslations('name'),
        'rate' => $c->rate,
        'is_default' => $c->is_default,
        'is_active' => $c->is_active,
        'sort_order' => $c->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('price_types')) {
      $data['price_types'] = PriceType::with('currency')->get()->map(fn($pt) => [
        'slug' => $pt->slug,
        'name' => $pt->getTranslations('name'),
        'description' => $this->shouldIncludeDescriptions() ? $pt->getTranslations('description') : null,
        'is_default' => $pt->is_default,
        'currency_code' => $pt->currency?->code,
        'sort_order' => $pt->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('families')) {
      $data['families'] = ProductFamily::all()->map(fn($f) => [
        'external_code' => $f->external_code,
        'code' => $f->code,
        'slug' => $f->slug,
        'name' => $f->getTranslations('name'),
        'meta_schema' => $f->meta_schema,
        'sort_order' => $f->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('types')) {
      $data['types'] = ProductType::with('attributes')->get()->map(fn($t) => [
        'external_code' => $t->external_code,
        'family_external_code' => $t->family?->external_code,
        'code' => $t->code,
        'slug' => $t->slug,
        'name' => $t->getTranslations('name'),
        'meta' => $t->meta,
        'sort_order' => $t->sort_order,
        'attached_attributes' => $t->attributes->map(fn($a) => [
          'code' => $a->code,
          'is_variant_only' => $a->pivot->is_variant_only,
        ])->toArray(),
      ])->toArray();
    }

    if ($this->shouldExport('categories')) {
      $allCategories = Category::all()->keyBy('id');
      $data['categories'] = $allCategories->map(fn($c) => [
        'external_code' => $c->external_code,
        'slug' => $c->slug,
        'name' => $c->getTranslations('name'),
        'description' => $this->shouldIncludeDescriptions() ? $c->getTranslations('description') : null,
        'parent_external_code' => $c->parent_id ? $allCategories->get($c->parent_id)?->external_code : null,
        'sort_order' => $c->sort_order,
      ])->values()->toArray();
    }

    if ($this->shouldExport('attributes')) {
      $data['attributes'] = Attribute::with('options')->get()->map(fn($a) => [
        'external_code' => $a->external_code,
        'code' => $a->code,
        'type' => $a->type,
        'name' => $a->getTranslations('name'),
        'option_param_type' => $a->option_param_type,
        'is_multiple' => $a->is_multiple,
        'settings' => $a->settings,
        'options' => $a->options->map(fn($o) => [
          'external_code' => $o->external_code,
          'slug' => $o->slug,
          'value' => $o->getTranslations('value'),
          'param' => $o->param,
          'meta' => $o->meta,
        ])->toArray(),
      ])->toArray();
    }

    if ($this->shouldExport('price_groups')) {
      $data['price_groups'] = PriceGroup::all()->map(fn($pg) => [
        'external_code' => $pg->external_code,
        'product_family_external_code' => $pg->family?->external_code,
        'slug' => $pg->slug,
        'name' => $pg->getTranslations('name'),
        'description' => $this->shouldIncludeDescriptions() ? $pg->getTranslations('description') : null,
        'meta' => $pg->meta,
        'sort_order' => $pg->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('complex_dictionaries')) {
      $data['complex_dictionaries'] = ComplexDictionary::with('records')->get()->map(fn($cd) => [
        'external_code' => $cd->external_code,
        'code' => $cd->code,
        'name' => $cd->getTranslations('name'),
        'meta_schema' => $cd->meta_schema,
        'sort_order' => $cd->sort_order,
        'records' => $cd->records->map(fn($r) => [
          'external_code' => $r->external_code,
          'slug' => $r->slug,
          'name' => $r->getTranslations('name'),
          'meta' => $r->meta,
        ])->toArray(),
      ])->toArray();
    }

    if ($this->shouldExport('products')) {
      $data['products'] = Product::query()
        ->with(['type', 'category', 'unit', 'attributeValues.attribute', 'variants.attributeValues.attribute', 'variants.prices.type', 'variants.stocks.warehouse'])
        ->get()->map(fn($p) => $this->mapProduct($p))
        ->toArray();
    }

    if ($this->shouldExport('pipelines')) {
      $data['pipelines'] = Pipeline::all()->map(fn($pl) => [
        'external_code' => $pl->external_code,
        'code' => $pl->code,
        'slug' => $pl->slug,
        'name' => $pl->getTranslations('name'),
        'description' => $this->shouldIncludeDescriptions() ? $pl->getTranslations('description') : null,
        'schema' => $pl->schema,
        'is_active' => $pl->is_active,
        'sort_order' => $pl->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('pipeline_scenarios')) {
      $data['pipeline_scenarios'] = PipelineScenario::with('pipeline')->get()->map(fn($ps) => [
        'external_code' => $ps->external_code,
        'pipeline_external_code' => $ps->pipeline?->external_code,
        'code' => $ps->code,
        'name' => $ps->getTranslations('name'),
        'description' => $this->shouldIncludeDescriptions() ? $ps->getTranslations('description') : null,
        'ui_state' => $ps->ui_state,
        'is_active' => $ps->is_active,
        'sort_order' => $ps->sort_order,
      ])->toArray();
    }

    if ($this->shouldExport('binding_rules')) {
      $data['binding_rules'] = BindingRule::with(['pipeline', 'parent', 'child'])->get()->map(fn($br) => [
        'external_code' => $br->external_code,
        'pipeline_external_code' => $br->pipeline?->external_code,
        'name' => $br->name,
        'role' => $br->role,
        'parent_type_key' => $this->resolveTypeKey($br->parent_type),
        'parent_external_code' => $br->parent?->external_code,
        'child_type_key' => $this->resolveTypeKey($br->child_type),
        'child_external_code' => $br->child?->external_code,
        'conditions' => $br->conditions,
        'static_meta' => $br->static_meta,
        'quantity_formula' => $br->quantity_formula,
        'is_required' => $br->is_required,
        'sort_order' => $br->sort_order,
      ])->toArray();
    }

    $this->saveJson($path, $data);
    //$this->exportServices();
  }

  protected function mapProduct(Product $p): array
  {
    return [
      'external_code' => $p->external_code,
      'product_type_external_code' => $p->type?->external_code,
      'category_external_code' => $p->category?->external_code,
      'unit_code' => $p->unit?->slug,
      'catalog_type' => $p->catalog_type,
      'slug' => $p->slug,
      'code' => $p->code,
      'name' => $p->getTranslations('name'),
      'short_description' => $this->shouldIncludeDescriptions() ? $p->getTranslations('short_description') : null,
      'description' => $this->shouldIncludeDescriptions() ? $p->getTranslations('description') : null,
      'is_active' => $p->is_active,
      'eav' => $this->mapEav($p),
      'variants' => $p->variants->map(fn($v) => [
        'external_code' => $v->external_code,
        'price_group_external_code' => $v->priceGroup?->external_code,
        'sku' => $v->sku,
        'name' => $v->getTranslations('name'),
        'cost_price' => $v->cost_price,
        'currency' => $v->currency,
        'is_default' => $v->is_default,
        'is_active' => $v->is_active,
        'is_manual_pricing' => $v->is_manual_pricing,
        'markup' => $v->prices->where('type.slug', 'retail')->first()?->markup_percent,
        'stock' => $v->stock,
        'eav' => $this->mapEav($v),
      ])->toArray(),
    ];
  }

  protected function mapEav($model): array
  {
    $eav = [];
    foreach ($model->attributeValues as $val) {
      $code = $val->attribute->code;
      $value = $val->value_option_id ? $val->option?->external_code :
        ($val->value_complex_id ? $val->complexRecord?->external_code :
          ($val->value_boolean !== null ? $val->value_boolean :
            ($val->value_numeric !== null ? $val->value_numeric : $val->value_string)));

      if ($val->attribute->is_multiple) {
        $eav[$code][] = $value;
      } else {
        $eav[$code] = $value;
      }
    }
    return $eav;
  }

  protected function resolveTypeKey(?string $morphClass): ?string
  {
    return $morphClass;
  }

  protected function exportServices(): void //ToDO
  {
    $path = base_path($this->option('services'));
    $services = Product::where('catalog_type', CatalogType::SERVICE)
      ->with(['attributeValues.attribute', 'variants.attributeValues.attribute', 'unit', 'category'])
      ->get();

    if ($services->isEmpty()) return;

    $this->line("Экспорт услуг в $path...");

    $export = [
      'categories' => Category::where('external_code', 'like', 'cat_srv_%')->get()->pluck('name', 'slug')->toArray(),
      'services' => $services->map(fn($s) => [
        'slug' => $s->slug,
        'category' => $s->category?->slug,
        'unit' => $s->unit?->slug,
        'name' => $s->getTranslations('name'),
        'eav' => $this->mapEav($s),
        'prices' => $s->variants->mapWithKeys(function ($v) {
          $targetMaterial = $v->attributeValues->firstWhere('attribute.code', 'target_material')?->option?->slug;
          return [$targetMaterial ?? 'default' => [
            'cost_price' => $v->cost_price,
            'currency' => $v->currency,
            'markup' => $v->prices->where('type.slug', 'retail')->first()?->markup_percent,
          ]];
        })->toArray(),
      ])->values()->toArray(),
    ];

    $this->saveJson($path, $export);
  }

  protected function saveJson(string $path, array $data): void
  {
    $directory = dirname($path);
    if (!File::exists($directory)) {
      File::makeDirectory($directory, 0755, true);
    }

    File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  }

  protected function shouldExport(string $section): bool
  {
    $only = $this->parseArrayOption('only');
    $exclude = $this->parseArrayOption('exclude');

    if (!empty($only) && !in_array($section, $only, true)) {
      return false;
    }

    if (!empty($exclude) && in_array($section, $exclude, true)) {
      return false;
    }

    return true;
  }

  protected function shouldIncludeDescriptions(): bool
  {
    return !(bool)$this->option('no-descriptions');
  }

  protected function parseArrayOption(string $optionName): array
  {
    $values = (array)$this->option($optionName);
    $result = [];

    foreach ($values as $value) {
      if (is_string($value)) {
        foreach (explode(',', $value) as $part) {
          $trimmed = trim($part);
          if ($trimmed !== '') {
            $result[] = $trimmed;
          }
        }
      }
    }

    return array_unique($result);
  }
}
