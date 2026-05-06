<?php

namespace App\Providers;

use App\Support\Menu;
use App\Support\ModuleRegistry;
use App\Support\SearchRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Menu::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(SearchRegistry::class);
    }

    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        $registry = $this->app->make(ModuleRegistry::class);
        $menu = $this->app->make(Menu::class);

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->bootModule($modulePath, $registry, $menu);
        }
    }

    protected function bootModule(string $path, ModuleRegistry $registry, Menu $menu): void
    {
        $name = basename($path);

        // Manifest (required) — defines label/group/icon/permissions
        $manifestFile = $path.'/module.php';

        $manifest = File::exists($manifestFile)
            ? require $manifestFile
            : ['name' => $name];

        $manifest['name'] = $name;
        $manifest['path'] = $path;

        $registry->register($name, $manifest);

        // Migrations
        $migrationsPath = $path.'/Database/Migrations';
        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Routes
        $routesFile = $path.'/routes.php';
        if (File::exists($routesFile)) {
            Route::middleware('web')->group($routesFile);
        }

        // Views — namespace = kebab-case module name
        $viewsPath = $path.'/Livewire/views';
        if (File::isDirectory($viewsPath)) {
            $kebab = Str::kebab($name);
            $this->loadViewsFrom($viewsPath, $kebab);
        }

        // Livewire components — auto-register everything in {Module}/Livewire/
        $this->registerLivewireComponents($name, $path);

        // Menu entries
        $menuFile = $path.'/menu.php';
        if (File::exists($menuFile)) {
            foreach ((array) require $menuFile as $item) {
                $item['module'] = $name;
                $menu->add($item);
            }
        }
    }

    protected function registerLivewireComponents(string $module, string $path): void
    {
        $livewireDir = $path.'/Livewire';

        if (! File::isDirectory($livewireDir)) {
            return;
        }

        $kebab = str(Str::snake($module, '-'))->lower();

        foreach (File::allFiles($livewireDir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str($file->getRelativePathname())
                ->beforeLast('.php')
                ->replace(['/', '\\'], '\\')
                ->toString();

            $class = "App\\Modules\\{$module}\\Livewire\\{$relative}";

            if (! class_exists($class)) {
                continue;
            }

            $alias = $kebab.'.'.str(Str::snake(basename($relative), '-'))->lower();
            Livewire::component($alias, $class);
        }
    }
}
