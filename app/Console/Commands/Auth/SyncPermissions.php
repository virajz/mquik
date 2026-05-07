<?php

namespace App\Console\Commands\Auth;

use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'auth:sync-permissions
                            {--prune : Delete permissions no longer declared in any module manifest}';

    protected $description = 'Discover permissions from each module.php manifest and upsert them into the permissions table';

    public function handle(ModuleRegistry $modules, PermissionRegistrar $registrar): int
    {
        $declared = collect();

        foreach ($modules->all() as $module) {
            foreach ((array) ($module['permissions'] ?? []) as $slug) {
                if (! is_string($slug) || $slug === '') {
                    continue;
                }
                $declared[$slug] = true;
            }
        }

        $existing = Permission::query()->pluck('name')->all();
        $created = 0;

        foreach ($declared->keys() as $slug) {
            if (in_array($slug, $existing, true)) {
                continue;
            }
            Permission::create(['name' => $slug, 'guard_name' => 'web']);
            $created++;
        }

        $deleted = 0;
        if ($this->option('prune')) {
            $orphaned = Permission::query()->whereNotIn('name', $declared->keys()->all())->pluck('name')->all();
            if (! empty($orphaned)) {
                Permission::query()->whereIn('name', $orphaned)->delete();
                $deleted = count($orphaned);
            }
        }

        $registrar->forgetCachedPermissions();

        $this->info(sprintf(
            'Permissions synced: %d declared, %d new, %d pruned.',
            $declared->count(),
            $created,
            $deleted,
        ));

        return self::SUCCESS;
    }
}
