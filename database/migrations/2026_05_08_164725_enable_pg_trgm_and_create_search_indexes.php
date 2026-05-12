<?php

use App\Support\ModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Postgres-only fuzzy-search support for Master Search.
 *
 * Defers to `auth:sync-search-indexes` for the actual index work so the index logic has one
 * source of truth and is re-runnable any time `$searchableFields` changes on a model.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        Artisan::call('auth:sync-search-indexes');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (app(ModuleRegistry::class)->all() as $module) {
            $modelClass = $module['searchable']['model'] ?? null;
            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }
            if (! method_exists($modelClass, 'searchableFields')) {
                continue;
            }
            $instance = new $modelClass;
            $table = $instance->getTable();
            foreach ($modelClass::searchableFields() as $field) {
                if (! preg_match('/^[a-z][a-z0-9_]*$/i', $field)) {
                    continue;
                }
                $idx = "{$table}_{$field}_trgm_idx";
                if (strlen($idx) > 63) {
                    $idx = substr($idx, 0, 63);
                }
                DB::statement(sprintf('DROP INDEX IF EXISTS "%s"', $idx));
            }
        }

        // Extension intentionally left in place — other code may use it.
    }
};
