<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Discovers where a model record is referenced via incoming foreign keys, so a
 * blocked delete can tell the user exactly what to clear first. Driver-agnostic
 * (uses Laravel's schema introspection), so it works on Postgres and SQLite.
 */
class RecordReferences
{
    /**
     * Map of human label => reference count for every table/column that points
     * at this record. Only non-zero counts are returned, busiest first.
     *
     * @return array<string, int>
     */
    public static function for(Model $model): array
    {
        $table = $model->getTable();
        $id = $model->getKey();
        if ($id === null) {
            return [];
        }

        $usages = [];

        foreach (self::incomingForeignKeys($table) as [$refTable, $refColumn]) {
            $count = DB::table($refTable)->where($refColumn, $id)->count();
            if ($count > 0) {
                $label = self::label($refTable);
                $usages[$label] = ($usages[$label] ?? 0) + $count;
            }
        }

        arsort($usages);

        return $usages;
    }

    /**
     * A short human sentence fragment like "3 Job Cards, 2 Spares" — or null
     * when nothing references the record.
     */
    public static function summary(Model $model, int $max = 4): ?string
    {
        $usages = self::for($model);
        if ($usages === []) {
            return null;
        }

        $parts = [];
        foreach (array_slice($usages, 0, $max, true) as $label => $count) {
            $noun = $count === 1 ? Str::singular($label) : $label;
            $parts[] = $count.' '.$noun;
        }

        $remaining = count($usages) - $max;
        if ($remaining > 0) {
            $parts[] = 'and '.$remaining.' more';
        }

        return implode(', ', $parts);
    }

    /**
     * Every [referencing_table, referencing_column] whose FK targets $table.
     *
     * @return list<array{0: string, 1: string}>
     */
    protected static function incomingForeignKeys(string $table): array
    {
        $refs = [];

        foreach (Schema::getTables() as $t) {
            $name = is_array($t) ? ($t['name'] ?? null) : ($t->name ?? null);
            if (! $name) {
                continue;
            }

            foreach (Schema::getForeignKeys($name) as $fk) {
                if (($fk['foreign_table'] ?? null) !== $table) {
                    continue;
                }
                $column = $fk['columns'][0] ?? null;
                if ($column) {
                    $refs[] = [$name, $column];
                }
            }
        }

        return $refs;
    }

    protected static function label(string $table): string
    {
        return Str::headline($table);
    }
}
