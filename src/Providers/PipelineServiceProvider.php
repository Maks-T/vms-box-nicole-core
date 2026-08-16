<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class PipelineServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    $this->app->singleton(PipelineTreeService::class, fn () => new PipelineTreeService());
  }

  public function boot(): void
  {
    // Регистрация базовых моделей ядра в PipelineEntityResolver

    // Модификация товара (SKU)
    PipelineEntityResolver::register(ProductVariant::class, [
      'parent_id' => fn (ProductVariant $v) => $v->product_id,
      'name' => function (ProductVariant $v, string $locale) {
        $transName = $v->getTranslation('name', $locale);
        if (!empty($transName)) {
          return $transName;
        }
        return $v->product?->getTranslation('name', $locale) ?: ($v->name ?? $v->sku);
      },
      'slug' => fn (ProductVariant $v) => $v->product?->slug ?? $v->slug,
      'type_code' => fn (ProductVariant $v) => $v->product?->type?->code ?? 'general',
    ]);

    // Базовый товар
    PipelineEntityResolver::register(Product::class, [
      'parent_id' => fn (Product $p) => $p->category_id,
      'type_code' => fn (Product $p) => $p->type?->code ?? 'general',
    ]);

    // Запись умного справочника
    PipelineEntityResolver::register(ComplexDictionaryRecord::class, [
      'parent_id' => fn (ComplexDictionaryRecord $r) => $r->dictionary_id,
      'type_code' => fn (ComplexDictionaryRecord $r) => $r->dictionary?->code ?? 'dictionary_record',
    ]);

    // Категория
    PipelineEntityResolver::register(Category::class, [
      'parent_id' => fn (Category $c) => $c->parent_id,
      'type_code' => fn () => 'category',
    ]);
  }
}
