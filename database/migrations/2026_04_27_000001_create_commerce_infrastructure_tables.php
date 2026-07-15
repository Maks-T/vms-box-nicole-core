<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('units', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('slug')->unique();
      $table->string('code')->nullable()->index();
      $table->jsonb('name');
      $table->jsonb('symbol')->nullable();

      $table->integer('sort_order')->default(0);

      $table->settings();
      $table->timestamps();
    });

    Schema::create('currencies', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('code', 3)->unique();
      $table->jsonb('name');
      $table->string('symbol', 10);
      $table->jsonb('symbol_native')->nullable();
      $table->decimal('rate', 15, 4)->default(1.0000);
      $table->boolean('is_default')->default(false);

      $table->integer('sort_order')->default(0);

      $table->boolean('is_active')->default(true);
      $table->settings();
      $table->timestamps();
    });

    Schema::create('price_types', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('slug')->unique();
      $table->jsonb('name');
      $table->jsonb('description')->nullable();
      $table->boolean('is_default')->default(false);
      $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

      $table->integer('sort_order')->default(0);

      $table->settings();
      $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->string('slug')->unique();
      $table->jsonb('name');
      $table->jsonb('description')->nullable();
      $table->string('address')->nullable();
      $table->decimal('latitude', 10, 8)->nullable();
      $table->decimal('longitude', 11, 8)->nullable();
      $table->jsonb('schedule')->nullable();
      $table->string('phone')->nullable();
      $table->string('email')->nullable();
      $table->boolean('is_pickup_point')->default(true);
      $table->boolean('is_active')->default(true);
      $table->integer('sort_order')->default(0);
      $table->settings();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('warehouses');
    Schema::dropIfExists('price_types');
    Schema::dropIfExists('currencies');
    Schema::dropIfExists('units');
  }
};
