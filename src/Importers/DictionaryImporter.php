<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\ComplexDictionary;
use Nicole\Box\Core\Models\ComplexDictionaryRecord;

class DictionaryImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Complex Dictionaries & Records';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $dictionaries = $data['complex_dictionaries'] ?? [];
    if (empty($dictionaries)) return;

    $bar = $command->getOutput()->createProgressBar(count($dictionaries));

    foreach ($dictionaries as $dictData) {
      $dictionary = ComplexDictionary::updateOrCreate(
        ['external_code' => $dictData['external_code']],
        [
          'code' => $dictData['code'],
          'name' => $dictData['name'],
          'meta_schema' => $dictData['meta_schema'] ?? null, 
          'is_active' => true,
        ]
      );

      
      foreach ($dictData['records'] ?? [] as $recordData) {
        ComplexDictionaryRecord::updateOrCreate(
          ['external_code' => $recordData['external_code']],
          [
            'dictionary_id' => $dictionary->id,
            'slug' => $recordData['slug'],
            'name' => $recordData['name'],
            'meta' => $recordData['meta'] ?? [], 
            'is_active' => true,
          ]
        );
      }
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
