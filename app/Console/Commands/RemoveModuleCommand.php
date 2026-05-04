<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RemoveModuleCommand extends Command
{
    protected $signature = 'module:remove {name : Studly-cased module name (e.g. DemoModule)}
                                          {--force : Skip confirmation prompt}
                                          {--keep-data : Skip migration rollback (keeps the table)}';

    protected $description = 'Remove an application module — rolls back migrations, deletes module dir + test file';

    public function handle(): int
    {
        $studly = Str::studly($this->argument('name'));
        $modulePath = app_path("Modules/{$studly}");
        $testPath = base_path("tests/Feature/Modules/{$studly}Test.php");

        if (! File::isDirectory($modulePath)) {
            $this->components->error("Module {$studly} does not exist at {$modulePath}");

            return self::FAILURE;
        }

        $this->components->warn("About to remove module: {$studly}");
        $this->components->bulletList([
            "Module dir:  app/Modules/{$studly}",
            "Test file:   tests/Feature/Modules/{$studly}Test.php",
            $this->option('keep-data')
                ? 'Migrations:  KEPT (table will remain)'
                : 'Migrations:  ROLLED BACK (table will be dropped)',
        ]);

        if (! $this->option('force') && ! $this->confirm('Proceed?', false)) {
            $this->components->info('Cancelled.');

            return self::SUCCESS;
        }

        // 1. Roll back migrations belonging to this module
        if (! $this->option('keep-data')) {
            $migrationsPath = "{$modulePath}/Database/Migrations";

            if (File::isDirectory($migrationsPath)) {
                $this->components->task('Rolling back migrations', function () use ($migrationsPath) {
                    Artisan::call('migrate:rollback', [
                        '--path' => str_replace(base_path().'/', '', $migrationsPath),
                        '--realpath' => false,
                        '--force' => true,
                    ]);

                    return true;
                });
            }
        }

        // 2. Delete module directory
        $this->components->task("Deleting {$modulePath}", function () use ($modulePath) {
            return File::deleteDirectory($modulePath);
        });

        // 3. Delete test file
        if (File::exists($testPath)) {
            $this->components->task("Deleting {$testPath}", function () use ($testPath) {
                return File::delete($testPath);
            });
        }

        // 4. Clean up empty parent dirs
        $testParent = dirname($testPath);
        if (File::isDirectory($testParent) && empty(File::files($testParent))) {
            File::deleteDirectory($testParent);
        }

        $this->newLine();
        $this->components->info("Module {$studly} removed.");
        $this->line('  Run: composer dump-autoload');
        $this->newLine();

        return self::SUCCESS;
    }
}
