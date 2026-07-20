<?php

namespace App\Console\Commands;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Console\Command;

/**
 * One-shot rebuild of the whole system: drop → migrate → seed baseline + real
 * master data. The test user (test@example.com / Super Admin) is recreated by
 * the DatabaseSeeder, so login access survives every reset.
 */
class SystemReset extends Command
{
    protected $signature = 'system:reset
        {--force : Skip the confirmation prompt}
        {--skip-master-data : Rebuild without importing the previous-ERP master data}';

    protected $description = 'Reset the system: migrate:fresh + baseline seeders + master data (keeps the test user).';

    public function handle(): int
    {
        if ($this->getLaravel()->environment('production')) {
            $this->error('system:reset is disabled in production.');

            return self::FAILURE;
        }

        $this->warn('This DROPS every table and rebuilds the database from scratch.');
        $this->line('  → the test user <info>test@example.com</info> (Super Admin) is recreated by the seeder.');

        if (! $this->option('force') && ! $this->confirm('Continue?', false)) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $this->components->task('Dropping + re-running migrations', fn () => $this->callSilent('migrate:fresh', ['--force' => true]) === 0);

        $this->components->task('Seeding baseline masters + test user', fn () => $this->callSilent('db:seed', ['--force' => true]) === 0);

        if (! $this->option('skip-master-data')) {
            $this->newLine();
            $this->info('Importing previous-ERP master data…');
            $this->call('db:seed', ['--class' => MasterDataSeeder::class, '--force' => true]);
        }

        $this->newLine();
        $this->components->info('System reset complete. Log in as test@example.com (password: password).');

        return self::SUCCESS;
    }
}
