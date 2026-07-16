<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Attribute;
use Nicole\Box\Core\Models\AttributeOption;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Support\Constants\MediaCollection;

class AttributeImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Attributes & Options';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $attributes = $data['attributes'] ?? [];
    if (empty($attributes)) {
      return;
    }

    $bar = $command->getOutput()->createProgressBar(count($attributes));

    $complexDictMap = ComplexDictionary::pluck('id', 'code')->toArray();

    foreach ($attributes as $attrData) {
      $complexDictId = null;
      if ($attrData['type'] === Attribute::TYPE_COMPLEX) {
        $complexDictId = $complexDictMap[$attrData['code']] ?? null;
      }

      $attribute = Attribute::updateOrCreate(
        ['external_code' => $attrData['external_code']],
        [
          'code' => $attrData['code'],
          'name' => $attrData['name'],
          'type' => $attrData['type'],
          'option_param_type' => $attrData['option_param_type'] ?? null,
          'complex_dictionary_id' => $complexDictId,
          'is_active' => true,
          'is_multiple' => $attrData['is_multiple'] ?? false,
          'settings' => $attrData['settings'] ?? null,
        ]
      );

      $sortOrder = 10;
      foreach ($attrData['options'] ?? [] as $optData) {
        $option = AttributeOption::updateOrCreate(
          ['external_code' => $optData['external_code']],
          [
            'attribute_id' => $attribute->id,
            'slug' => $optData['slug'],
            'value' => $optData['value'],

            'param' => $optData['param'] ?? null,

            'meta' => $optData['meta'] ?? null,
            'sort_order' => $sortOrder,
          ]
        );

        $imagePath = $optData['meta']['image'] ?? null;

        if ($imagePath) {
          $fullPath = base_path('import/export_images/' . ltrim($imagePath, '/'));

          if (File::exists($fullPath)) {
            $existingMedia = $option->getFirstMedia(MediaCollection::MAIN);
            $fileName = basename($fullPath);

            if (!$existingMedia || $existingMedia->file_name !== $fileName) {
              $option->clearMediaCollection(MediaCollection::MAIN);
              $option->addMedia($fullPath)
                ->preservingOriginal()
                ->withCustomProperties(['skip_conversions' => true])
                ->toMediaCollection(MediaCollection::MAIN);
            }
          } else {
            $command->warn("\n⚠ Опция {$option->slug}: Изображение не найдено -> {$fullPath}");
          }
        }

        $sortOrder += 10;
      }

      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
