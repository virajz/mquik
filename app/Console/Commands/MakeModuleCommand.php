<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Studly-cased module name (e.g. VendorMaster)} {--force : Overwrite existing files}';

    protected $description = 'Scaffold a new application module under app/Modules/';

    public function handle(): int
    {
        $studly = Str::studly($this->argument('name'));
        $kebab = Str::kebab($studly);
        $snake = Str::snake($studly);
        $table = Str::snake(Str::pluralStudly($studly));
        $force = (bool) $this->option('force');

        $modulePath = app_path("Modules/{$studly}");

        if (File::isDirectory($modulePath) && ! $force) {
            $this->components->error("Module {$studly} already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $replacements = [
            '{{ studlyName }}' => $studly,
            '{{ kebabName }}' => $kebab,
            '{{ snakeName }}' => $snake,
            '{{ tableName }}' => $table,
        ];

        $files = [
            'module.stub' => 'module.php',
            'menu.stub' => 'menu.php',
            'routes.stub' => 'routes.php',
            'model.stub' => "Models/{$studly}.php",
            'factory.stub' => "Database/Factories/{$studly}Factory.php",
            'seeder.stub' => "Database/Seeders/{$studly}Seeder.php",
            'livewire-index.stub' => 'Livewire/Index.php',
            'livewire-form.stub' => 'Livewire/Form.php',
            'view-index.stub' => 'Livewire/views/index.blade.php',
            'view-form.stub' => 'Livewire/views/form.blade.php',
        ];

        foreach ($files as $stub => $relative) {
            $this->writeStub($stub, "{$modulePath}/{$relative}", $replacements, $force);
        }

        // Migration with timestamp
        $migrationName = date('Y_m_d_His')."_create_{$table}_table.php";
        $this->writeStub(
            'migration.stub',
            "{$modulePath}/Database/Migrations/{$migrationName}",
            $replacements,
            $force
        );

        // Test (lives outside module dir so Pest auto-discovers it)
        $testPath = base_path("tests/Feature/Modules/{$studly}Test.php");
        $this->writeStub('test.stub', $testPath, $replacements, $force);

        $this->newLine();
        $this->components->info("Module {$studly} scaffolded.");
        $this->components->bulletList([
            "Module path:  app/Modules/{$studly}",
            "Route:        /{$kebab}",
            "Livewire:     {$kebab}.index",
            "View:         {$kebab}::index",
            "Test:         tests/Feature/Modules/{$studly}Test.php",
        ]);
        $this->newLine();
        $this->line('  Next steps:');
        $this->line('    composer dump-autoload');
        $this->line('    php artisan migrate');
        $this->line("    php artisan test --filter={$studly}");
        $this->newLine();

        return self::SUCCESS;
    }

    protected function writeStub(string $stub, string $destination, array $replacements, bool $force): void
    {
        $stubPath = app_path("Console/stubs/module/{$stub}");

        if (! File::exists($stubPath)) {
            $this->components->error("Stub not found: {$stub}");

            return;
        }

        if (File::exists($destination) && ! $force) {
            $this->components->warn('Skip (exists): '.str_replace(base_path().'/', '', $destination));

            return;
        }

        $contents = strtr(File::get($stubPath), $replacements);
        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $contents);

        $this->components->info('Created: '.str_replace(base_path().'/', '', $destination));
    }
}
