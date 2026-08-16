<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Services\Calculator\PipelineTreeService;
use Nicole\Box\Core\Support\Constants\EntityType as ET;
use Nicole\Box\Core\Support\Pipelines\Adapters\CategoryAdapter;
use Nicole\Box\Core\Support\Pipelines\Adapters\ComplexDictionaryRecordAdapter;
use Nicole\Box\Core\Support\Pipelines\Adapters\ProductAdapter;
use Nicole\Box\Core\Support\Pipelines\Adapters\ProductVariantAdapter;
use Nicole\Box\Core\Support\Pipelines\PipelineEntityResolver;

class PipelineServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    $this->app->singleton(PipelineTreeService::class, fn() => new PipelineTreeService());
  }

  public function boot(): void
  {
    // Регистрация чистых адаптеров сущностей
    PipelineEntityResolver::registerAdapter(new ProductVariantAdapter());
    PipelineEntityResolver::registerAdapter(new ProductAdapter());
    PipelineEntityResolver::registerAdapter(new ComplexDictionaryRecordAdapter());
    PipelineEntityResolver::registerAdapter(new CategoryAdapter());

    // Регистрация целевых типов для слотов схемы
    PipelineEntityResolver::registerTargetEntity(
      type: ET::PRODUCT_TYPE,
      label: ET::label(ET::PRODUCT_TYPE),
      optionsLoader: fn() => ProductType::where('is_active', true)->pluck('name', 'code')->toArray()
    );

    PipelineEntityResolver::registerTargetEntity(
      type: ET::COMPLEX_DICTIONARY,
      label: ET::label(ET::COMPLEX_DICTIONARY),
      optionsLoader: fn() => ComplexDictionary::where('is_active', true)->pluck('name', 'code')->toArray()
    );

    PipelineEntityResolver::registerTargetEntity(
      type: ET::SCALAR,
      label: ET::label(ET::SCALAR),
      optionsLoader: fn() => []
    );
  }
}
