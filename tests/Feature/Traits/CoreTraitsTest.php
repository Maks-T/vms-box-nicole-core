<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Feature\Traits;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;
use Nicole\Box\Core\Models\Product;
use Nicole\Box\Core\Models\ProductVariant;

class CoreTraitsTest extends TestCase
{
  use LazilyRefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Подменяем публичный диск на изолированное виртуальное хранилище в памяти
    Storage::fake('public');

    // Создаем обязательный канал продаж
    Channel::create([
      'code' => 'widget',
      'name' => ['ru' => 'Виджет калькулятора'],
      'is_active' => true,
    ]);

    // Создаем базовую валюту (Рубли) для работы калькулятора цен
    $rub = Currency::factory()->create([
      'code' => 'RUB',
      'rate' => 1.0,
      'is_default' => true,
    ]);

    // Создаем базовый тип цен по умолчанию
    PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);
  }

  /**
   * Сценарий: Тестирование получения настроек каналов продаж в HasSettings.
   */
  public function test_has_settings_trait_helper_methods(): void
  {
    $product = Product::factory()->create();

    // Проверяем вызов геттера настроек конкретного канала
    $channelSettings = $product->getChannelSettings('widget');
    $this->assertIsArray($channelSettings);
    $this->assertTrue($channelSettings['is_public']);

    // Проверяем вызов метода проверки активности в канале
    $isEnabled = $product->isEnabledInChannel('widget');
    $this->assertTrue($isEnabled);
  }

  /**
   * Сценарий: Тестирование работы с медиафайлами, каскадного наследования снизу вверх (от SKU к Товар).
   */
  public function test_has_nicole_media_trait_flow(): void
  {
    // 1. Создаем товар и его дефолтную модификацию без фото
    /** @var Product $product */
    $product = Product::factory()->create();

    /** @var ProductVariant $variant */
    $variant = ProductVariant::factory()->create([
      'product_id' => $product->id,
      'is_default' => true,
      'is_active' => true,
    ]);

    // Подгружаем связь вариантов в память товара
    $product->load('variants');

    // Проверяем, что изначально у обоих объектов превью возвращает null
    $this->assertNull($variant->getPreviewUrl());
    $this->assertNull($product->getPreviewUrl());

    // 2. Имитируем загрузку изображения в дефолтную модификацию (SKU)
    $variant->addMedia(UploadedFile::fake()->image('granite_sku.jpg'))
      ->toMediaCollection('main');

    $variant->refresh();

    // Теперь модификация должна вернуть URL захешированного превью
    $variantPreviewUrl = $variant->getPreviewUrl();
    $this->assertNotNull($variantPreviewUrl);
    $this->assertStringContainsString('preview', $variantPreviewUrl);

    // Сбрасываем устаревший кэш связи вариантов у товара и подгружаем заново с новыми медиаданными
    $product->unsetRelation('variants')->load('variants');

    // 3. Проверяем каскадный поиск: базовый товар должен автоматически унаследовать
    // превью у своей дефолтной модификации, так как у него самого фото отсутствует.
    $productPreviewUrl = $product->getPreviewUrl();
    $this->assertNotNull($productPreviewUrl);
    $this->assertEquals($variantPreviewUrl, $productPreviewUrl);
  }

}
