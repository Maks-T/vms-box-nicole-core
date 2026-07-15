<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Filament;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Tests\Traits\InteractsWithFilamentAdmin;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\ListProductFamilies;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\CreateProductFamily;
use Nicole\Box\Core\Filament\Resources\ProductFamilies\Pages\EditProductFamily;
use Livewire\Livewire;

class ProductFamilyResourceTest extends TestCase
{
  use LazilyRefreshDatabase;
  use InteractsWithFilamentAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpFilamentAdmin();
  }

  /**
   * Сценарий: Проверка отображения списка семейств товаров в таблице Filament.
   */
  public function test_can_render_list_product_families_page(): void
  {
    $this->actingAs($this->adminUser);

    $family = ProductFamily::factory()->create(['code' => 'stone_family']);

    Livewire::test(ListProductFamilies::class)
      ->assertSuccessful()
      ->assertCanSeeTableRecords([$family]);
  }

  /**
   * Сценарий: Создание нового семейства товаров со схемой полей в репитере.
   */
  public function test_can_create_product_family_via_form(): void
  {
    $this->actingAs($this->adminUser);

    Livewire::test(CreateProductFamily::class)
      ->set('data.name.ru', 'Каменные плиты')
      ->set('data.name.en', 'Stone Slabs')
      ->set('data.code', 'stone_slabs')
      ->set('data.slug', 'stone-slabs')
      ->set('data.is_active', true)
      // Наполняем схему физических параметров семейства через репитер
      ->set('data.meta_schema', [
        'param_row_1' => [
          'key' => 'max_slabs_stack',
          'type' => 'number',
          'label' => [
            'ru' => 'Макс. плит в стопке',
            'en' => 'Max Slabs in Stack',
          ],
          'width' => 1,
        ]
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    // Проверяем успешную запись в БД
    $family = ProductFamily::where('code', 'stone_slabs')->first();
    $this->assertNotNull($family);
    $this->assertNotEmpty($family->meta_schema);
    $this->assertEquals('max_slabs_stack', $family->meta_schema[0]['key']);
  }

  /**
   * Сценарий: Изменение активности существующего семейства товаров.
   */
  public function test_can_edit_product_family_via_form(): void
  {
    $this->actingAs($this->adminUser);

    $family = ProductFamily::factory()->create([
      'code' => 'accessory',
      'is_active' => true,
    ]);

    Livewire::test(EditProductFamily::class, [
      'record' => $family->getKey(),
    ])
      ->set('data.is_active', false)
      ->call('save')
      ->assertHasNoFormErrors();

    $this->assertFalse($family->refresh()->is_active);
  }

}