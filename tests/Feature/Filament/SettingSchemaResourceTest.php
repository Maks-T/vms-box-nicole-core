<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\SettingSchema;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\Pages\ListSettingSchemas;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\Pages\CreateSettingSchema;
use Nicole\Box\Core\Filament\Resources\SettingSchemas\Pages\EditSettingSchema;
use Livewire\Livewire;

class SettingSchemaResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка схем настроек в таблице.
   */
  public function test_can_render_list_setting_schemas_page(): void
  {
    $this->actingAs($this->adminUser);

    $schema = SettingSchema::updateOrCreate(
      ['entity_type' => 'test_entity'],
      ['meta_schema' => []]
    );

    Livewire::test(ListSettingSchemas::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$schema]);
  }

  /**
   * Сценарий: Создание новой схемы настроек для сущности.
   */
  public function test_can_create_setting_schema_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateSettingSchema::class)
      ->set('data.entity_type', 'attribute')
      // Наполняем схему настроек канала продаж через Repeater
      ->set('data.meta_schema', [
        'field_row_1' => [
          'key' => 'is_promoted',
          'label' => [
            'ru' => 'Рекомендовать',
            'en' => 'Promote',
          ],
          'type' => 'boolean',
          'width' => 1,
        ]
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем успешную запись в БД
    $schema = SettingSchema::where('entity_type', 'attribute')->first();
    $this->assertNotNull($schema);
    $this->assertNotEmpty($schema->meta_schema);
    $this->assertEquals('is_promoted', $schema->meta_schema[0]['key']);
  }

}