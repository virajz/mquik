<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-task work timers on the Vehicle Inspection Order scope lines.
 *
 * A technician starts / pauses / completes each task line (a labour job like
 * PMS or Wheel Alignment, a requested repair, or a described service). Elapsed
 * time accumulates in `duration_seconds`; `run_started_at` marks the current
 * running segment (null when not running). Only one line may run at a time per
 * technician.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->foreignId('labour_id')->nullable()->after('service_package_id')->constrained('labours')->nullOnDelete();
            $table->foreignId('requested_repair_id')->nullable()->after('labour_id')->constrained('requested_repairs')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->after('requested_repair_id')->constrained('employees')->nullOnDelete();

            $table->string('work_status', 15)->default('pending')->after('is_additional'); // pending / in_progress / paused / completed
            $table->dateTime('run_started_at')->nullable()->after('work_status');           // current running segment start
            $table->unsignedInteger('duration_seconds')->default(0)->after('run_started_at'); // accumulated completed time
            $table->dateTime('completed_at')->nullable()->after('duration_seconds');

            $table->index(['technician_id', 'work_status']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->dropIndex(['technician_id', 'work_status']);
            $table->dropConstrainedForeignId('labour_id');
            $table->dropConstrainedForeignId('requested_repair_id');
            $table->dropConstrainedForeignId('technician_id');
            $table->dropColumn(['work_status', 'run_started_at', 'duration_seconds', 'completed_at']);
        });
    }
};
