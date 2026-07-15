<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\Category;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\ListCategories;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\CreateCategory;
use Nicole\Box\Core\Filament\Resources\Categories\Pages\EditCategory;
use Livewire\Livewire;

class CategoryResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin(); // Настраиваем окружение администратора
  }

  /**
   * Сценарий: Проверка отображения списка категорий в таблице Filament.
   */
  public function test_can_render_list_categories_page(): void
  {
    $this->actingAs($this->adminUser);

    $category = Category::factory()->create(['slug' => 'kitchen-countertops']);

    Livewire::test(ListCategories::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$category]);
  }

  /**
   * Сценарий: Создание новой корневой категории через форму.
   */
  public function test_can_create_category_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateCategory::class)
      ->set('data.name.ru', 'Столешницы')
      ->set('data.name.en', 'Countertops')
      ->set('data.slug', 'countertops')
      ->set('data.is_active', true)
      ->call('create')
      ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
      'slug' => 'countertops',
      'is_active' => true,
    ]);
  }

  /**
   * Сценарий: Создание вложенной подкатегории с указанием родительской через форму.
   */
  public function test_can_create_subcategory_with_parent_relation(): void
  {
    $this->actingAs($this->adminUser);

    // Создаем родительскую категорию через фабрику
    /** @var Category $parentCategory */
    $parentCategory = Category::factory()->create([
      'slug' => 'artificial-stone',
    ]);

    // Создаем подкатегорию, привязанную к родителю через поле формы
    Livewire::test(CreateCategory::class)
      ->set('data.name.ru', 'Акриловые столешницы')
      ->set('data.name.en', 'Acrylic Countertops')
      ->set('data.slug', 'acrylic-countertops')
      ->set('data.parent_id', $parentCategory->id)
      ->set('data.is_active', true)
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем правильность построения иерархии вложенных множеств в БД
    $subcategory = Category::where('slug', 'acrylic-countertops')->first();
    $this->assertNotNull($subcategory);
    $this->assertEquals($parentCategory->id, $subcategory->parent_id);
  }

  /**
   * Сценарий: Изменение активности категории через форму редактирования.
   */
  public function test_can_edit_category_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $category = Category::factory()->create([
      'slug' => 'bathroom-sinks',
      'is_active' => true,
    ]);

    Livewire::test(EditCategory::class, [
      'record' => $category->getKey(),
    ])
      ->set('data.is_active', false)
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertFalse($category->refresh()->is_active);
  }
}