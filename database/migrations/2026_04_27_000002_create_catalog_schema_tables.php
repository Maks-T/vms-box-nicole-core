<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // 1. Реестр каналов продаж
    Schema::create('channels', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code')->unique();
      $table->jsonb('name');
      $table->boolean('is_active')->default(true);

      $table->integer('sort_order')->default(0);

      $table->timestamps();
    });

    // 2. Схемы настроек каналов
    Schema::create('setting_schemas', function (Blueprint $table) {
      $table->id();
      $table->string('entity_type')->unique();
      $table->jsonb('meta_schema');

      $table->timestamps();
    });

    // 3. Семейства товаров
    Schema::create('product_families', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code')->unique();
      $table->string('slug')->unique();

      $table->jsonb('name');
      $table->string('icon')->nullable();
      $table->jsonb('meta_schema')->nullable();

      $table->integer('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->settings();
      $table->timestamps();
    });

    // 3.1. Создание таблицы ценовых групп
    Schema::create('price_groups', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();

      $table->foreignId('product_family_id')
        ->nullable()
        ->constrained('product_families')
        ->nullOnDelete();

      $table->string('slug')->unique();
      $table->jsonb('name');
      $table->jsonb('description')->nullable();
      $table->jsonb('meta');

      $table->boolean('is_active')->default(true)->index();
      $table->integer('sort_order')->default(0);

      $table->settings();
      $table->timestamps();

      $table->index(['product_family_id', 'is_active']);
    });

    // 4. Умные справочники
    Schema::create('complex_dictionaries', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code')->unique();
      $table->jsonb('name');
      $table->jsonb('meta_schema')->nullable();

      $table->integer('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->settings();
      $table->timestamps();
    });

    // 4.1. Записи умных справочников
    Schema::create('complex_dictionary_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('dictionary_id')->constrained('complex_dictionaries')->cascadeOnDelete();
      $table->string('external_code')->nullable()->index();
      $table->string('slug')->nullable();
      $table->jsonb('name');
      $table->jsonb('meta');

      $table->integer('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });

    // 5. Атрибуты (Свойства / Фильтры)
    Schema::create('attributes', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code')->unique();
      $table->string('type'); // string, numeric, boolean, dictionary, complex

      $table->string('option_param_type')->nullable(); // none, string, numeric, boolean

      $table->jsonb('name');
      $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('complex_dictionary_id')->nullable()->constrained('complex_dictionaries')->nullOnDelete();
      $table->boolean('is_multiple')->default(false);

      $table->integer('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->settings();
      $table->timestamps();
    });

    // 5.1. Значения для обычных справочников
    Schema::create('attribute_options', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
      $table->string('slug')->index();

      $table->string('param')->nullable();

      $table->jsonb('value');
      $table->jsonb('meta')->nullable();

      $table->integer('sort_order')->default(0);
      $table->settings();
      $table->timestamps();
    });

    // 6. Типы товаров
    Schema::create('product_types', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code')->unique();
      $table->string('slug')->unique();
      $table->foreignId('family_id')->constrained('product_families')->cascadeOnDelete();
      $table->jsonb('name');
      $table->string('icon')->nullable();
      $table->jsonb('meta')->nullable();

      $table->integer('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->settings();
      $table->timestamps();
    });

    // 7. Привязка Атрибутов (Pivot)
    Schema::create('attribute_product_type', function (Blueprint $table) {
      $table->id();
      $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
      $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
      $table->boolean('is_required')->default(false);
      $table->boolean('is_variant_only')->default(false);
      $table->integer('sort_order')->default(0);
      $table->unique(['product_type_id', 'attribute_id'], 'idx_attr_prod_type_unique');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attribute_product_type');
    Schema::dropIfExists('product_types');
    Schema::dropIfExists('attribute_options');
    Schema::dropIfExists('attributes');
    Schema::dropIfExists('complex_dictionary_records');
    Schema::dropIfExists('complex_dictionaries');
    Schema::dropIfExists('price_groups');
    Schema::dropIfExists('product_families');
    Schema::dropIfExists('setting_schemas');
    Schema::dropIfExists('channels');
  }
};
