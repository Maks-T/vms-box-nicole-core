<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\ProductFamily;
use Nicole\Box\Core\Models\ProductType;
use Nicole\Box\Core\Models\Attribute;

class FamilyTypeImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Families & Product Types';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $families = $data['families'] ?? [];
    $types = $data['types'] ?? [];

    if (empty($families) && empty($types)) {
      return;
    }

    $bar = $command->getOutput()->createProgressBar(count($families) + count($types));

    // Импорт Семейств (Families)
    $familyIdMap = [];
    foreach ($families as $fData) {
      $family = ProductFamily::updateOrCreate(
        ['external_code' => $fData['external_code']],
        [
          'code' => $fData['code'],
          'slug' => $fData['slug'] ?? Str::slug($fData['code'], '-'),
          'name' => $fData['name'],
          'meta_schema' => $fData['meta_schema'] ?? null,
          'is_active' => true,
        ]
      );

      $familyIdMap[$fData['external_code']] = $family->id;
      $bar->advance();
    }

    // Импорт Типов товаров (Product Types)
    foreach ($types as $tData) {
      $familyId = $familyIdMap[$tData['family_external_code']] ?? null;

      $type = ProductType::updateOrCreate(
        ['external_code' => $tData['external_code']],
        [
          'family_id' => $familyId,
          'code' => $tData['code'],
          'slug' => $tData['slug'] ?? Str::slug($tData['code'], '-'),
          'name' => $tData['name'],
          'meta' => $tData['meta'] ?? null,
          'is_active' => true,
        ]
      );

      if (!empty($tData['attached_attributes'])) {
        $syncData = [];
        $sort = 10;
        foreach ($tData['attached_attributes'] as $attrMap) {
          $attributeId = Attribute::where('code', $attrMap['code'])->value('id');
          if ($attributeId) {
            $syncData[$attributeId] = [
              'is_variant_only' => $attrMap['is_variant_only'],
              'is_required' => false,
              'sort_order' => $sort
            ];
            $sort += 10;
          }
        }
        $type->attributes()->sync($syncData);
      }

      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
