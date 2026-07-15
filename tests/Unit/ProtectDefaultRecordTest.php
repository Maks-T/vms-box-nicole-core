<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;

class ProtectDefaultRecordTest extends TestCase
{
  use LazilyRefreshDatabase; 

  /**
   * Сценарий 1: Попытка удаления дефолтной (базовой) валюты должна приводить к ошибке
   */
  public function test_it_prevents_deletion_of_default_currency(): void
  {
    $defaultCurrency = Currency::factory()->create([
      'code' => 'RUB',
      'is_default' => true,
    ]);

    // Ожидаем выброс системного исключения
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(__('System default record cannot be deleted.'));

    // Попытка удаления должна быть заблокирована трейтом HasGlobalDefault
    $defaultCurrency->delete();
  }

  /**
   * Сценарий 2: Недефолтная валюта должна удаляться без каких-либо препятствий
   */
  public function test_it_allows_deletion_of_non_default_currency(): void
  {
    // Создаем обязательную дефолтную валюту (чтобы база была валидна)
    Currency::factory()->create([
      'code' => 'RUB',
      'is_default' => true,
    ]);

    // Создаем обычную валюту (USD)
    $nonDefaultCurrency = Currency::factory()->create([
      'code' => 'USD',
      'is_default' => false,
    ]);

    $nonDefaultCurrencyId = $nonDefaultCurrency->id;

    // Удаляем ее
    $nonDefaultCurrency->delete();

    // Проверяем, что запись физически пропала из БД
    $this->assertDatabaseMissing('currencies', [
      'id' => $nonDefaultCurrencyId,
    ]);
  }

  /**
   * Сценарий 3: Попытка удаления дефолтного типа цен также должна приводить к ошибке.
   */
  public function test_it_prevents_deletion_of_default_price_type(): void
  {
    $rub = Currency::factory()->create(['code' => 'RUB', 'rate' => 1.0, 'is_default' => true]);

    $defaultPriceType = PriceType::factory()->create([
      'slug' => 'retail',
      'is_default' => true,
      'currency_id' => $rub->id,
    ]);

    // Ожидаем выброс исключения
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage(__('System default record cannot be deleted.'));

    // Попытка удаления
    $defaultPriceType->delete();
  }

}
