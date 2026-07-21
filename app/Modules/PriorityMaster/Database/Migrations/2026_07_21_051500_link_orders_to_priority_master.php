<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidate the two hardcoded priority enums onto PriorityMaster so the
 * workshop has one urgency list instead of three:
 *
 *   vehicle_inspection_orders.work_priority  (normal|high|urgent)
 *   internal_part_orders.order_priority      (normal|urgent|breakdown|critical)
 *
 * Existing values are matched to the master by name before the string columns
 * are dropped; anything unmatched is left null rather than guessed at.
 */
return new class extends Migration
{
    /** @var array<string, string> table => old string column */
    private const TARGETS = [
        'vehicle_inspection_orders' => 'work_priority',
        'internal_part_orders' => 'order_priority',
    ];

    public function up(): void
    {
        foreach (self::TARGETS as $table => $oldColumn) {
            Schema::table($table, function (Blueprint $t) use ($oldColumn) {
                $t->foreignId('priority_id')->nullable()->after($oldColumn)
                    ->constrained('priorities')->nullOnDelete();
            });

            $this->backfill($table, $oldColumn);

            Schema::table($table, function (Blueprint $t) use ($oldColumn) {
                $t->dropColumn($oldColumn);
            });
        }
    }

    /**
     * Map old lowercase enum values onto master rows by name. Masters are seeded
     * after migrations on a fresh install, so a missing row leaves the FK null.
     */
    private function backfill(string $table, string $oldColumn): void
    {
        $ids = DB::table('priorities')->pluck('id', 'name');

        foreach ($ids as $name => $id) {
            DB::table($table)
                ->whereRaw('upper('.$oldColumn.') = ?', [strtoupper((string) $name)])
                ->update(['priority_id' => $id]);
        }
    }

    public function down(): void
    {
        foreach (self::TARGETS as $table => $oldColumn) {
            Schema::table($table, function (Blueprint $t) use ($oldColumn) {
                $t->string($oldColumn, 20)->default('normal');
                $t->dropConstrainedForeignId('priority_id');
            });
        }
    }
};
