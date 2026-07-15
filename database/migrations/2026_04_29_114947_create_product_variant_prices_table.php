<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      Schema::create('product_variant_prices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
        $table->foreignId('price_type_id')->constrained('price_types')->cascadeOnDelete();

        $table->decimal('markup_percent', 15, 10)->default(0.0000000000);

        $table->timestamps();

        $table->unique(['product_variant_id', 'price_type_id'], 'idx_variant_price_type_unique');
      });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_prices');
    }
};
