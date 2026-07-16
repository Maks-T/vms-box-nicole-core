<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // Базовые товары
    Schema::create('products', function (Blueprint $table) {
      $table->id();
      $table->string('catalog_type')->default('product')->index();
      $table->string('external_code')->nullable()->index();

      $table->string('code')->nullable()->unique()->index();

      $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();

      $table->jsonb('name');
      $table->string('slug')->unique();
      $table->jsonb('short_description')->nullable();
      $table->jsonb('description')->nullable();

      $table->decimal('min_price', 15, 2)->default(0)->index();

      $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

      $table->boolean('is_active')->default(true)->index();
      $table->integer('sort_order')->default(0);
      $table->settings();
      $table->timestamps();
    });

    // Варианты (SKU - Торговые предложения)
    Schema::create('product_variants', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();

      $table->foreignId('price_group_id')
        ->nullable()
        ->constrained('price_groups')
        ->nullOnDelete();

      $table->jsonb('name')->nullable();
      $table->string('sku')->unique();

      $table->decimal('cost_price', 15, 2)->default(0);
      $table->string('currency', 3)->default('RUB');

      $table->decimal('stock', 15, 3)->default(0);
      $table->boolean('is_default')->default(false)->index();

      $table->boolean('is_active')->default(true)->index();
      $table->boolean('is_manual_pricing')->default(false)->index();

      $table->integer('sort_order')->default(0);
      $table->settings();
      $table->timestamps();

      $table->index('product_id', 'idx_product_variants_product_id');
      $table->index(['price_group_id']);
    });

    // EAV-значения с композитным индексом
    Schema::create('product_attribute_values', function (Blueprint $table) {
      $table->id();
      $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
      $table->morphs('attributable');
      $table->string('value_string')->nullable();
      $table->decimal('value_numeric', 15, 4)->nullable();
      $table->boolean('value_boolean')->nullable();
      $table->foreignId('value_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
      $table->foreignId('value_complex_id')->nullable()->constrained('complex_dictionary_records')->nullOnDelete();
      $table->foreignId('value_entity_id')->nullable()->constrained('products')->nullOnDelete();

      $table->index(['attribute_id', 'value_numeric'], 'idx_eav_numeric');
      $table->index(['attribute_id', 'value_option_id'], 'idx_eav_option');
      $table->index(['attribute_id', 'value_complex_id'], 'idx_eav_complex');
      $table->index(['attribute_id', 'value_entity_id'], 'idx_eav_entity');

      // Композитный индекс для быстрого EAV-поиска
      $table->index(
        ['attributable_type', 'attributable_id', 'attribute_id'],
        'idx_eav_lookup_compound'
      );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('product_attribute_values');
    Schema::dropIfExists('product_variants');
    Schema::dropIfExists('products');
  }
};
