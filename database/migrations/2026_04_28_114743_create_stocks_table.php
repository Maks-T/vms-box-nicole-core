<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved', 15, 3)->default(0);

            $table->settings();
            $table->timestamps();

            $table->unique(['product_variant_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
