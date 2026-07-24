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
      $table->jsonb('description')->nullable();
      $table->jsonb('schema')->nullable(); // Мета-схема ролей/слотов
      $table->boolean('is_active')->default(true);
      $table->integer('sort_order')->default(0);

      $table->settings();

      $table->timestamps();
    });

    Schema::create('pipeline_scenarios', function (Blueprint $table) {
      $table->id();
      $table->foreignId('pipeline_id')->constrained('pipelines')->cascadeOnDelete();

      $table->string('code')->index();
      $table->string('external_code')->nullable()->index();

      $table->jsonb('name');
      $table->jsonb('description')->nullable();
      $table->jsonb('ui_state')->nullable(); // Поля формы в админке
      $table->boolean('is_active')->default(true);
      $table->integer('sort_order')->default(0);
      $table->timestamps();

      $table->unique(['pipeline_id', 'code'], 'idx_pipeline_scenario_code');
    });

    Schema::create('binding_rules', function (Blueprint $table) {
      $table->id();
      $table->string('external_code')->nullable()->index();
      $table->foreignId('pipeline_id')->nullable()->constrained('pipelines')->cascadeOnDelete();
      $table->foreignId('scenario_id')->nullable()->constrained('pipeline_scenarios')->nullOnDelete();

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

    DB::statement('CREATE INDEX idx_pipeline_scenarios_ui_state ON pipeline_scenarios USING GIN (ui_state);');
    DB::statement('CREATE INDEX idx_binding_rules_conditions ON binding_rules USING GIN (conditions);');
    DB::statement('CREATE INDEX idx_binding_rules_static_meta ON binding_rules USING GIN (static_meta);');
  }

  public function down(): void
  {
    Schema::dropIfExists('binding_rules');
    Schema::dropIfExists('pipeline_scenarios');
    Schema::dropIfExists('pipelines');
  }
};
