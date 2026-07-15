<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\ListProductTypes;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\CreateProductType;
use Nicole\Box\Core\Filament\Resources\ProductTypes\Pages\EditProductType;
use Livewire\Livewire;

class ProductTypeResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin(); // Инициализируем администратора
  }

  /**
   * Сценарий: Проверка отображения списка типов товаров в таблице.
   */
  public function test_can_render_list_product_types_page(): void
  {
    $this->actingAs($this->adminUser);

    $productType = ProductType::factory()->create(['code' => 'quartz_stone']);

    Livewire::test(ListProductTypes::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$productType]);
  }

  /**
   * Сценарий: Создание нового типа товара через форму Filament.
   */
  public function test_can_create_product_type_via_form(): void
  {
    $this->actingAs($this->adminUser);

    // Сначала создаем семейство товаров, к которому привяжем наш тип
    $family = ProductFamily::factory()->create([
      'code' => 'stone',
    ]);

    Livewire::test(CreateProductType::class)
      ->set('data.name.ru', 'Акриловый камень')
      ->set('data.name.en', 'Acrylic Stone')
      ->set('data.code', 'acrylic_stone')
      ->set('data.slug', 'acrylic-stone')
      ->set('data.family_id', $family->id) // Связываем с созданным семейством
      ->set('data.pricing_mode', 'manual')
      ->set('data.is_active', true)
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем физическое появление записи в БД
    $this->assertDatabaseHas('product_types', [
      'code' => 'acrylic_stone',
      'family_id' => $family->id,
    ]);
  }

  /**
   * Сценарий: Проверка валидации обязательных полей формы создания типа товара.
   */
  public function test_product_type_fields_are_validated(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateProductType::class)
      ->set('data.name.ru', 'Тип без кода и семейства')
      ->set('data.code', '') // Оставляем пустым
      ->set('data.family_id', null) // Оставляем пустым
      ->call('create')
      ->assertHasFormErrors([
        'code' => 'required',
        'family_id' => 'required',
      ]);
  }

  /**
   * Сценарий: Изменение активности существующего типа товара через форму редактирования.
   */
  public function test_can_edit_product_type_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $productType = ProductType::factory()->create([
      'code' => 'kitchen_sink',
      'is_active' => true,
    ]);

    Livewire::test(EditProductType::class, [
      'record' => $productType->getKey(),
    ])
      ->set('data.is_active', false) // Деактивируем тип товара
      ->call('save')
      ->assertHasNoFormErrors();

    // Сверяем новое значение активности в базе данных
    $this->assertFalse($productType->refresh()->is_active);
  }

}