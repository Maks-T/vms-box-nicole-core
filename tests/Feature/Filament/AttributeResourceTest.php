<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\ListAttributes;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\CreateAttribute;
use Nicole\Box\Core\Filament\Resources\Attributes\Pages\EditAttribute;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;

class AttributeResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin(); // Инициализируем админа
  }

  /**
   * Сценарий: Проверка отображения списка атрибутов в таблице Filament.
   */
  public function test_can_render_list_attributes_page(): void
  {
    $this->actingAs($this->adminUser);

    $attribute = Attribute::factory()->create(['code' => 'brand_color']);

    Livewire::test(ListAttributes::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$attribute]);
  }

  /**
   * Сценарий: Создание нового строкового атрибута через форму.
   */
  public function test_can_create_string_attribute_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateAttribute::class)
      ->set('data.name.ru', 'Бренд изделия')
      ->set('data.name.en', 'Product Brand')
      ->set('data.code', 'product_brand')
      ->set('data.type', Attribute::TYPE_STRING)
      ->set('data.is_active', true)
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем физическое появление атрибута в БД
    $this->assertDatabaseHas('attributes', [
      'code' => 'product_brand',
      'type' => Attribute::TYPE_STRING,
    ]);
  }

  /**
   * Сценарий: Проверка валидации полей (код и тип атрибута обязательны).
   */
  public function test_attribute_fields_are_validated(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateAttribute::class)
      ->set('data.name.ru', 'Характеристика')
      ->set('data.code', '') // Оставляем пустым
      ->set('data.type', '') // Оставляем пустым
      ->call('create')
      ->assertHasFormErrors([
        'code' => 'required',
        'type' => 'required',
      ]);
  }

  /**
   * Сценарий: Редактирование существующего атрибута.
   */
  public function test_can_edit_attribute_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $attribute = Attribute::factory()->create([
      'code' => 'stone_thickness',
      'type' => Attribute::TYPE_NUMERIC,
      'is_active' => true, // Изначально активен
    ]);

    Livewire::test(EditAttribute::class, [
      'record' => $attribute->getKey(),
    ])
      ->set('data.is_active', false) // Меняем активность на неактивный
      ->call('save')
      ->assertHasNoFormErrors();

    // Проверяем, что значение в БД действительно изменилось
    $this->assertFalse($attribute->refresh()->is_active);
  }

  /**
   * Сценарий: Проверка создания опции для справочного атрибута через Relation Manager.
   */
  public function test_can_create_attribute_option_via_relation_manager(): void
  {
    $this->actingAs($this->adminUser);

    $attribute = Attribute::factory()->create([
      'type' => Attribute::TYPE_DICTIONARY,
    ]);

    Livewire::test(\Nicole\Box\Core\Filament\Resources\Attributes\RelationManagers\OptionsRelationManager::class, [
      'ownerRecord' => $attribute,
      'pageClass' => \Nicole\Box\Core\Filament\Resources\Attributes\Pages\EditAttribute::class,
    ])
      ->mountAction(TestAction::make('create')->table())
      ->set('mountedActions.0.data.value.ru', 'Зеленый')  // Записываем данные в mountedActions
      ->set('mountedActions.0.data.value.en', 'Green')
      ->set('mountedActions.0.data.slug', 'green')
      ->callMountedAction() // Подтверждаем открытый экшен
      ->assertHasNoFormErrors(); // Проверяем отсутствие ошибок

    // Проверяем появление опции в базе данных
    $this->assertDatabaseHas('attribute_options', [
      'attribute_id' => $attribute->id,
      'slug' => 'green',
    ]);
  }

}
