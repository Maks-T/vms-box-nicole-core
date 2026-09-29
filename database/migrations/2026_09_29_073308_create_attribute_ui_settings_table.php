<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('attribute_ui_settings', function (Blueprint $table) {
      $table->id();

      // Исправленный синтаксис: unique() вызывается ДО constrained()
      $table->foreignId('attribute_id')
        ->unique()
        ->constrained('attributes')
        ->cascadeOnDelete();

      // 1. Отображение в таблицах (Grids)
      $table->boolean('show_in_variant_grid')->default(false); // В таблице модификаций (SKU)
      $table->boolean('show_in_product_grid')->default(false); // В таблице базовых товаров
      $table->string('grid_component_type')->default('auto');   // auto, color, badge, text, boolean
      $table->integer('grid_sort_order')->default(0);          // Порядок следования колонок

      // 2. Инлайн-редактирование прямо в строке таблицы
      $table->boolean('is_inline_editable')->default(false);   // Редактирование в ячейке без захода в карточку

      // 3. Фильтры и поиск в админке
      $table->boolean('is_filterable')->default(false);        // Быстрый фильтр в шапке таблицы
      $table->boolean('is_searchable')->default(false);        // Поиск через поисковую строку таблицы

      // 4. Права доступа (интеграция с ролями Filament Shield / Spatie)
      $table->jsonb('view_roles')->nullable();                 // Список ролей, видящих колонку (null = все)
      $table->jsonb('edit_roles')->nullable();                 // Список ролей с правом инлайн-правки (null = все)

      // 5. Запасной JSONB для любых кастомных UI-настроек в будущем
      $table->jsonb('ui_config')->nullable();

      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attribute_ui_settings');
  }
};