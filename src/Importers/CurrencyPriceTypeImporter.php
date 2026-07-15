<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Currency;
use Nicole\Box\Core\Models\PriceType;

class CurrencyPriceTypeImporter implements ImportModuleInterface
{
  /**
   * Название модуля (для вывода в консоль при импорте)
   */
  public function getName(): string
  {
    return 'Currencies & Price Types';
  }

  /**
   * Метод импорта и синхронизации валют и типов цен
   */
  public function run(array $settings, array $data, Command $command): void
  {
    $currencies = $data['currencies'] ?? [];
    $priceTypes = $data['price_types'] ?? [];

    if (empty($currencies) && empty($priceTypes)) {
      $command->warn('  ⚠ Skipped: No currencies or price types found in import_data.json.');
      return;
    }

    $bar = $command->getOutput()->createProgressBar(count($currencies) + count($priceTypes));

    // Настройки видимости каналов по умолчанию
    $defaultChannelSettings = [
      'channels' => [
        'widget' => ['is_public' => true],
        'catalog' => ['is_public' => true],
      ],
    ];

    // Импорт и синхронизация курсов валют
    foreach ($currencies as $currData) {
      Currency::updateOrCreate(
        ['code' => $currData['code']],
        [
          'symbol' => $currData['symbol'],
          'symbol_native' => $currData['symbol_native'] ?? null,
          'name' => $currData['name'],
          'rate' => (float)$currData['rate'],
          'is_default' => (bool)($currData['is_default'] ?? false),
          'is_active' => (bool)($currData['is_active'] ?? true),
          'settings' => $currData['settings'] ?? $defaultChannelSettings,
          'sort_order' => (int)($currData['sort_order'] ?? 0),
        ]
      );
      $bar->advance();
    }

    // Импорт типов цен в привязке к валютам
    foreach ($priceTypes as $ptData) {
      $currencyId = Currency::where('code', $ptData['currency_code'] ?? 'BYN')->value('id');

      PriceType::updateOrCreate(
        ['slug' => $ptData['slug']],
        [
          'name' => $ptData['name'],
          'description' => $ptData['description'] ?? null,
          'is_default' => (bool)($ptData['is_default'] ?? false),
          'currency_id' => $currencyId,
          'settings' => $ptData['settings'] ?? $defaultChannelSettings,
          'sort_order' => (int)($ptData['sort_order'] ?? 0),
        ]
      );
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
