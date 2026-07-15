<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Importers;

use Illuminate\Console\Command;
use Nicole\Box\Core\Importers\Contracts\ImportModuleInterface;
use Nicole\Box\Core\Models\Channel;
use Nicole\Box\Core\Models\SettingSchema;

class SettingsImporter implements ImportModuleInterface
{
  public function getName(): string
  {
    return 'Channels & Setting Schemas';
  }

  public function run(array $settings, array $data, Command $command): void
  {
    $channels = $settings['channels'] ?? [];
    $command->line("Importing Channels...");

    foreach ($channels as $code => $channelData) {
      Channel::updateOrCreate(
        ['code' => $code],
        [
          'name' => ['ru' => ucfirst($code), 'en' => ucfirst($code)],
          'is_active' => true,
        ]
      );
    }

    $schemas = $settings['setting_schemas'] ?? [];
    $bar = $command->getOutput()->createProgressBar(count($schemas));

    foreach ($schemas as $entityType => $fields) {
      SettingSchema::updateOrCreate(
        ['entity_type' => $entityType],
        ['meta_schema' => $fields] 
      );
      $bar->advance();
    }

    $bar->finish();
    $command->line('');
  }
}
