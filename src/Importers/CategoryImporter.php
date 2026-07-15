<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Category;

class CategoryImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Categories (Hierarchy)';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $categories = $data['categories'] ?? [];
    if (empty($categories)) return;

    $bar = $command->getOutput()->createProgressBar(count($categories) * 2);

    
    foreach ($categories as $catData) {
      Category::updateOrCreate(
        ['external_code' => $catData['external_code']],
        [
          'slug' => $catData['slug'],
          'name' => $catData['name'],
          'is_active' => true,
        ]
      );
      $bar->advance();
    }

    
    $idMap = Category::pluck('id', 'external_code')->toArray();

    
    foreach ($categories as $catData) {
      if (!empty($catData['parent_external_code'])) {
        $parentId = $idMap[$catData['parent_external_code']] ?? null;
        $categoryId = $idMap[$catData['external_code']] ?? null;

        if ($categoryId && $parentId) {
          Category::where('id', $categoryId)->update(['parent_id' => $parentId]);
        }
      }
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}