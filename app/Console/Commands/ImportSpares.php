<?php

namespace App\Console\Commands;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Console\Command;

/**
 * Imports the old-ERP spare master (Spare.csv) on its own, so a live server can
 * take the spares without re-running the whole master-data chain.
 *
 * Idempotent — spares are keyed on `legacy_key`, so re-running imports nothing
 * twice and an interrupted run can simply be repeated.
 */
class ImportSpares extends Command
{
    protected $signature = 'import:spares
        {--path= : Directory holding Spare.csv (defaults to database/seeders/data/master-data)}';

    protected $description = 'Import the previous-ERP spare master + its dated rate history from Spare.csv';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('seeders/data/master-data');

        if (! is_file($path.'/Spare.csv')) {
            $this->components->error('No Spare.csv in '.$path);

            return self::FAILURE;
        }

        $this->components->info('Importing spares from '.$path.'/Spare.csv …');

        $seeder = new MasterDataSeeder;
        $seeder->setContainer($this->getLaravel())->setCommand($this);
        $seeder->importSpares($path);

        $this->newLine();
        $this->components->info('Done. Re-run any time — already-imported spares are skipped.');

        return self::SUCCESS;
    }
}
