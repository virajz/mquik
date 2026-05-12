<?php

namespace App\Console\Commands\Auth;

use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncSearchIndexes extends Command
{
    protected $signature = 'auth:sync-search-indexes';

    protected $description = 'Discover Searchable model fields from each module manifest and create GIN trigram indexes for fuzzy Master Search. Postgres-only — no-op on other drivers. Idempotent.';

    public function handle(ModuleRegistry $modules): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('Search indexes are Postgres-only — current driver is '.DB::getDriverName().'. No-op.');

            return self::SUCCESS;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $created = 0;
        $skipped = [];

        foreach ($modules->all() as $module) {
            $modelClass = $module['searchable']['model'] ?? null;
            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }
            if (! method_exists($modelClass, 'searchableFields')) {
                continue;
            }

            /** @var Model $instance */
            $instance = new $modelClass;
            $table = $instance->getTable();

            foreach ($modelClass::searchableFields() as $field) {
                if (! preg_match('/^[a-z][a-z0-9_]*$/i', $field)) {
                    continue;
                }
                if (! Schema::hasColumn($table, $field)) {
                    continue;
                }

                $type = Schema::getColumnType($table, $field);
                // gin_trgm_ops requires text/varchar. Skip fixed-length char columns
                // (aadhar, pan, ifsc) — they're short, plain ILIKE is fast enough.
                if (! in_array($type, ['string', 'text', 'varchar'], true)) {
                    $skipped[] = "{$table}.{$field} ({$type})";

                    continue;
                }

                $indexName = $this->indexName($table, $field);
                DB::statement(sprintf(
                    'CREATE INDEX IF NOT EXISTS %s ON %s USING gin (%s gin_trgm_ops)',
                    $this->quote($indexName),
                    $this->quote($table),
                    $this->quote($field),
                ));
                $created++;
            }
        }

        $this->info("Trigram indexes ensured: {$created} eligible columns covered.");
        if (! empty($skipped)) {
            $this->line('Skipped (non-string column type): '.implode(', ', $skipped));
        }

        return self::SUCCESS;
    }

    protected function indexName(string $table, string $field): string
    {
        $name = "{$table}_{$field}_trgm_idx";

        return strlen($name) <= 63 ? $name : substr($name, 0, 63);
    }

    protected function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
