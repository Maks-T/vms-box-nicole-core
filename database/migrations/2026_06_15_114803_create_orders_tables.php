<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    // Статусы заказов
    Schema::create('order_statuses', function (Blueprint $table) {
      $table->id();
      $table->string('slug')->unique();
      $table->jsonb('name');
      $table->string('color', 30)->default('gray');

      $table->boolean('is_default')->default(false);
      $table->boolean('is_active')->default(true);
      $table->integer('sort_order')->default(0);
      $table->timestamps();
    });

    // Покупатели / Клиенты
    Schema::create('customers', function (Blueprint $table) {
      $table->id();
      $table->string('first_name')->nullable();
      $table->string('last_name')->nullable();
      $table->string('middle_name')->nullable();

      $table->string('phone')->nullable();
      $table->string('phone_normalized', 20)->nullable()->index();
      $table->string('email')->nullable()->index();
      $table->string('address')->nullable();

      $table->text('admin_notes')->nullable();

      $table->ipAddress('last_ip')->nullable();
      $table->timestamps();
    });

    // 3. Заказы / Расчеты
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique()->index();
      $table->string('external_code')->nullable()->index();

      // Связь с покупателем
      $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

      // Финансовые показатели всего расчета (соответствуют root-уровню SaveData)
      $table->decimal('grand_total', 15, 2);
      $table->string('currency', 3);
      $table->string('locale', 5);

      // Статус заказа
      $table->foreignId('status_id')->nullable()->constrained('order_statuses')->nullOnDelete();

      // Комментарии к заказу (соответствуют root-уровню SaveData)
      $table->text('customer_comment')->nullable();
      $table->text('manager_comment')->nullable();

      // Стейт калькулятора (соответствует calc_state из SaveData)
      $table->jsonb('calc_state')->nullable();

      // Ответственный менеджер (при удалении менеджера заказ сохраняется для истории)
      $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();
    });

    // 4. Секции / Изделия в заказе (результаты из results)
    Schema::create('order_sections', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

      $table->string('item_id')->nullable(); // Соответствует results[].id (может быть null)
      $table->string('type', 50)->nullable()->index(); // Соответствует results[].type (countertop, windowsill)
      $table->string('title'); // Соответствует results[].title

      // Финансовые показатели конкретной секции (соответствуют объекту results[].price)
      $table->decimal('price_total', 15, 2)->default(0.00);            // results[].price.total (до скидки)
      $table->decimal('price_grand_total', 15, 2)->default(0.00);      // results[].price.grand_total (итого)
      $table->decimal('price_vat', 15, 2)->default(0.00);              // results[].price.VAT
      $table->decimal('price_vat_percent', 5, 2)->default(0.00);       // results[].price.VAT_percent
      $table->decimal('price_discount', 15, 2)->default(0.00);         // results[].price.discount
      $table->decimal('price_discount_percent', 5, 2)->default(0.00);  // results[].price.discount_percent

      // Характеристики конкретного изделия (соответствуют массиву results[].description)
      $table->jsonb('description')->nullable();

      // Дерево сметы для рендеринга PDF (соответствует массиву results[].estimate)
      $table->jsonb('estimate')->nullable();

      // Универсальные мета-данные (properties, items, components)
      $table->jsonb('meta')->nullable();

      $table->timestamps();
    });

    // 5. Связанные физические товары из базы данных
    Schema::create('order_products', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
      $table->foreignId('order_section_id')->constrained('order_sections')->cascadeOnDelete();

      // Связь с товаром каталога (restrictOnDelete блокирует случайное удаление товара из истории продаж)
      $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();

      // Числовое количество для складского учета (например, 22.380 м² камня или 1.000 шт. моек)
      $table->decimal('quantity', 15, 3)->default(1.000);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('order_products');
    Schema::dropIfExists('order_sections');
    Schema::dropIfExists('orders');
    Schema::dropIfExists('customers');
    Schema::dropIfExists('order_statuses');
  }
};
