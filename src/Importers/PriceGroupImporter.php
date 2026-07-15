<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\PriceGroup;
use Nicole\Box\Core\Models\ProductFamily;

class PriceGroupImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Price Groups (Highload Books)';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $priceGroups = $data['price_groups'] ?? [];
    if (empty($priceGroups)) {
      return;
    }

    $bar = $command->getOutput()->createProgressBar(count($priceGroups));
    $familyIdMap = ProductFamily::pluck('id', 'external_code')->toArray();

    foreach ($priceGroups as $groupData) {
      $familyId = $familyIdMap[$groupData['product_family_external_code'] ?? ''] ?? null;

      PriceGroup::updateOrCreate(
        ['external_code' => $groupData['external_code']],
        [
          'product_family_id' => $familyId,
          'slug' => $groupData['slug'],
          'name' => $groupData['name'],
          'description' => $groupData['description'] ?? null,
          'meta' => $groupData['meta'] ?? [],
          'is_active' => true,
        ]
      );
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
