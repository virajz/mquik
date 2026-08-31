<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the technician first touched this line.
 *
 * `run_started_at` is the current segment only — it is cleared on every pause,
 * so it cannot answer "what time did work start". TAT needs both ends kept:
 * this stamp and `completed_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_inspection_order_scopes', 'work_started_at')) {
            Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
                $table->timestamp('work_started_at')->nullable()->after('run_started_at');
            });
        }

        // Best available truth for lines already running or already worked: the
        // current segment's start, else the first pause recorded against it.
        // No table alias in the UPDATE: Postgres allows it, SQLite does not,
        // and the suite runs on SQLite.
        DB::statement(<<<'SQL'
            update vehicle_inspection_order_scopes
            set work_started_at = coalesce(
                run_started_at,
                (select min(p.paused_at) from vehicle_inspection_order_pauses p
                  where p.vehicle_inspection_order_scope_id = vehicle_inspection_order_scopes.id)
            )
            where work_started_at is null
              and (run_started_at is not null or duration_seconds > 0)
        SQL);
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->dropColumn('work_started_at');
        });
    }
};
