<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->nestedSet();
            $table->string('external_code')->nullable()->index();
            $table->jsonb('name');
            $table->string('slug')->unique();
            $table->jsonb('description')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->settings();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('entity_type_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
