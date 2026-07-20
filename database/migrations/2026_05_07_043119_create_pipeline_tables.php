<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('pipelines', function (Blueprint $table) {
      $table->id();

      $table->string('code')->unique()->index();
      $table->string('slug')->nullable()->unique()->index();
      $table->string('external_code')->nullable()->index();

      $table->jsonb('name');
      $table->string('industry')->index();
      $table->jsonb('description')->nullable();
      $table->jsonb('ui_state')->nullable();
      $table->jsonb('schema')->nullable();
      $table->boolean('is_active')->default(true);
      $table->integer('sort_order')->default(0);
      $table->settings();
      $table->timestamps();
    });

    Schema::create('binding_rules', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->foreignId('pipeline_id')->nullable()->constrained('pipelines')->cascadeOnDelete();
      $table->string('name')->nullable();

      $table->string('role')->nullable()->index();

      $table->string('parent_type');
      $table->unsignedBigInteger('parent_id');

      $table->string('child_type')->nullable();
      $table->unsignedBigInteger('child_id')->nullable();

      $table->jsonb('conditions')->nullable();
      $table->jsonb('static_meta')->nullable();

      $table->string('quantity_formula')->default('1');
      $table->boolean('is_required')->default(false);
      $table->integer('sort_order')->default(0);
      $table->timestamps();

      $table->index(['parent_type', 'parent_id'], 'idx_rule_parent');
      $table->index('role', 'idx_binding_rules_role');
    });

    DB::statement('CREATE INDEX idx_binding_rules_conditions ON binding_rules USING GIN (conditions);');
    DB::statement('CREATE INDEX idx_binding_rules_static_meta ON binding_rules USING GIN (static_meta);');
  }

  public function down(): void
  {
    Schema::dropIfExists('binding_rules');
    Schema::dropIfExists('pipelines');
  }
};
