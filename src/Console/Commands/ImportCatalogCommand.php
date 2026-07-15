<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportCatalogCommand extends Command
{
  protected $signature = 'vms:import 
                            {--settings=import/import_settings.json : Path to settings JSON} 
                            {--data=import/import_data.json : Path to data JSON} 
                            {--services=import/import_services.json : Path to services JSON}';

  protected $description = 'Run the modular catalog and settings import';

  public function handle(): int
  {
    $settingsPath = base_path($this->option('settings'));
    $dataPath = base_path($this->option('data'));
    $servicesPath = base_path($this->option('services'));

    
    if (!File::exists($settingsPath)) {
      $this->error("Error: Settings file not found at {$settingsPath}");
      return self::FAILURE;
    }

    if (!File::exists($dataPath)) {
      $this->error("Error: Data file not found at {$dataPath}");
      return self::FAILURE;
    }

    $settings = json_decode(File::get($settingsPath), true) ?? [];
    $data = json_decode(File::get($dataPath), true) ?? [];

    
    if (File::exists($servicesPath)) {
      $data['services_import'] = json_decode(File::get($servicesPath), true) ?? [];
      $this->info("Services file loaded: {$this->option('services')}");
    } else {
      $this->warn("Services file not found: {$this->option('services')}. Skipping services data.");
    }

    
    $moduleClasses = config('nicole.import_modules', []);

    if (empty($moduleClasses)) {
      $this->warn("No import modules registered in config/nicole.php.");
      return self::SUCCESS;
    }

    $this->info("Starting VMS Catalog Import...");
    $this->newLine();

    DB::beginTransaction();
    try {
      foreach ($moduleClasses as $class) {
        if (!class_exists($class)) {
          $this->error("Module class not found: {$class}");
          DB::rollBack();
          return self::FAILURE;
        }

        $module = new $class();
        $this->info("Running module: {$module->getName()}");

        $module->run($settings, $data, $this);
      }

      DB::commit();

      $this->newLine();
      $this->info("Import completed successfully.");
      return self::SUCCESS;

    } catch (\Exception $e) {
      DB::rollBack();
      $this->newLine();
      $this->error("Import failed. Rolled back changes.");
      $this->error($e->getMessage());
      $this->error($e->getTraceAsString());
      return self::FAILURE;
    }
  }
}