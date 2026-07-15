<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DbOptimizeCommand extends Command
{
  protected $signature = 'vms:db-optimize';

  protected $description = 'Clusters and defragments PostgreSQL tables for EAV';

  public function handle(): int
  {
    $this->info('Starting database optimization...');

    try {
      $this->info('Clustering product_attribute_values table...');
      DB::statement('CLUSTER product_attribute_values USING idx_eav_lookup_compound;');

      $this->info('Running VACUUM FULL ANALYZE on product_attribute_values...');
      DB::statement('VACUUM FULL ANALYZE product_attribute_values;');

      $this->info('Running global database ANALYZE...');
      DB::statement('ANALYZE;');

      $this->info('Database optimization completed successfully.');
      return self::SUCCESS;
    } catch (Throwable $e) {
      $this->error('Database optimization failed: ' . $e->getMessage());
      return self::FAILURE;
    }
  }
}
