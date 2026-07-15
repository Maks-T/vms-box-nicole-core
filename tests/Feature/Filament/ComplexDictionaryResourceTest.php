<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\ListComplexDictionaries;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\CreateComplexDictionary;
use Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\EditComplexDictionary;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;

class ComplexDictionaryResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin(); // Инициализируем админа
  }

  /**
   * Сценарий: Проверка отображения списка умных справочников в таблице админки.
   */
  public function test_can_render_list_dictionaries_page(): void
  {
    $this->actingAs($this->adminUser);

    $dictionary = ComplexDictionary::factory()->create(['code' => 'stone_colors']);

    Livewire::test(ListComplexDictionaries::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$dictionary]);
  }

  /**
   * Сценарий: Создание нового умного справочника через форму.
   */
  public function test_can_create_complex_dictionary_via_form(): void
  {
    $this->actingAs($this->adminUser);

    // Заполняем основные поля и добавляем одну строку в схему полей через Repeater
    Livewire::test(CreateComplexDictionary::class)
      ->set('data.name.ru', 'Толщина плит')
      ->set('data.name.en', 'Slab Thickness')
      ->set('data.code', 'slab_thickness')
      ->set('data.is_active', true)
      ->set('data.meta_schema', [
        'block_id_1' => [
          'key' => 'thickness_value',
          'type' => 'number',
          'label' => [
            'ru' => 'Значение толщины',
            'en' => 'Thickness Value',
          ],
          'is_public' => true,
        ]
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем физическое появление справочника в базе данных и его схему
    $this->assertDatabaseHas('complex_dictionaries', [
      'code' => 'slab_thickness',
      'is_active' => true,
    ]);

    // Проверяем, что схема полей корректно записалась в JSONB-колонку
    $dictionary = ComplexDictionary::where('code', 'slab_thickness')->first();
    $this->assertNotEmpty($dictionary->meta_schema);
    $this->assertEquals('thickness_value', $dictionary->meta_schema[0]['key']);
  }

  /**
   * Сценарий: Проверка валидации (код справочника обязателен).
   */
  public function test_complex_dictionary_fields_are_validated(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateComplexDictionary::class)
      ->set('data.name.ru', 'Справочник без кода')
      ->set('data.code', '') // Передаем пустое поле
      ->call('create')
      ->assertHasFormErrors(['code' => 'required']);
  }

  /**
   * Сценарий: Изменение активности существующего умного справочника.
   */
  public function test_can_edit_complex_dictionary_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $dictionary = ComplexDictionary::factory()->create([
      'code' => 'cutting_groups',
      'is_active' => true,
    ]);

    Livewire::test(EditComplexDictionary::class, [
      'record' => $dictionary->getKey(),
    ])
      ->set('data.is_active', false) // Деактивируем справочник
      ->call('save')
      ->assertHasNoFormErrors();

    // Сверяем новое значение активности в БД
    $this->assertFalse($dictionary->refresh()->is_active);
  }

  /**
   * Сценарий: Проверка создания записи справочника через Relation Manager.
   */
  public function test_can_create_dictionary_record_via_relation_manager(): void
  {
    $this->actingAs($this->adminUser);

    // Создаем умный справочник со схемой полей, чтобы активировать поля мета-данных
    $dictionary = ComplexDictionary::factory()->create([
      'meta_schema' => [
        [
          'key' => 'thickness_value',
          'type' => 'number',
          'label' => [
            'ru' => 'Толщина',
            'en' => 'Thickness',
          ],
          'is_public' => true,
        ]
      ]
    ]);

    // Имитируем создание записи в таблице отношения
    Livewire::test(\Nicole\Box\Core\Filament\Resources\ComplexDictionaries\RelationManagers\RecordsRelationManager::class, [
      'ownerRecord' => $dictionary,
      'pageClass' => \Nicole\Box\Core\Filament\Resources\ComplexDictionaries\Pages\EditComplexDictionary::class,
    ])
      ->mountAction(TestAction::make('create')->table())
      ->set('mountedActions.0.data.name.ru', 'Группа 1')
      ->set('mountedActions.0.data.name.en', 'Group 1')
      ->set('mountedActions.0.data.slug', 'group_1')
      // Заполняем обязательное мета-поле из схемы
      ->set('mountedActions.0.data.meta.thickness_value', 12)
      ->set('mountedActions.0.data.is_active', true)
      ->callMountedAction() // Подтверждаем открытый экшен
      ->assertHasNoFormErrors();

    // Проверяем появление записи в базе данных
    $this->assertDatabaseHas('complex_dictionary_records', [
      'dictionary_id' => $dictionary->id,
      'slug' => 'group_1',
    ]);
  }

}