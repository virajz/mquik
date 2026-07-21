<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Row 6 (Inward/Outward) — the CSV asks for Entry Date & Time, Exit Date & Time
 * and TAT on one record, but this table stored one row per gate event (in OR
 * out), which cannot express a duration.
 *
 * Restructure to one row per *visit*: inward opens it, outward closes it, and
 * TAT is simply exited_at − entered_at. "Still inside" becomes `exited_at IS NULL`
 * instead of a fragile pairing query.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the indexes built on `direction` / `gated_at` first, and do it while
        // the table still has its original name so the generated index names match.
        // SQLite (used by the test suite) errors on a dropped column that an index
        // still references, where Postgres would cascade.
        Schema::table('gate_events', function (Blueprint $table) {
            $table->dropIndex(['direction', 'gated_at']);
            $table->dropIndex(['gated_at']);
            $table->dropIndex(['customer_vehicle_id', 'gated_at']);
        });

        Schema::rename('gate_events', 'gate_visits');

        Schema::table('gate_visits', function (Blueprint $table) {
            // The inward leg.
            $table->renameColumn('gated_at', 'entered_at');
        });

        Schema::table('gate_visits', function (Blueprint $table) {
            $table->index('entered_at');
            $table->index(['customer_vehicle_id', 'entered_at']);
        });

        Schema::table('gate_visits', function (Blueprint $table) {
            $table->foreignId('entry_gate_id')->nullable()->after('entered_at')
                ->constrained('gates')->nullOnDelete();
            $table->foreignId('parking_slot_id')->nullable()->after('entry_gate_id')
                ->constrained('parking_slots')->nullOnDelete();

            // The outward leg — null until the vehicle actually leaves.
            $table->dateTime('exited_at')->nullable()->after('parking_slot_id');
            $table->foreignId('exit_gate_id')->nullable()->after('exited_at')
                ->constrained('gates')->nullOnDelete();
            $table->string('outward_type', 30)->nullable()->after('exit_gate_id');
            $table->string('driver_type', 30)->nullable()->after('outward_type');
            $table->foreignId('delivered_by_id')->nullable()->after('driver_type')
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('exit_by_id')->nullable()->after('delivered_by_id')
                ->constrained('employees')->nullOnDelete();

            $table->foreignId('job_card_id')->nullable()->after('customer_id')
                ->constrained('job_cards')->nullOnDelete();
            $table->string('status', 20)->default('pending')->after('exit_by_id'); // pending | completed | cancelled

            $table->index('exited_at');
            $table->index(['status', 'entered_at']);
        });

        // `direction` is meaningless once a row spans both legs.
        Schema::table('gate_visits', function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }

    public function down(): void
    {
        Schema::table('gate_visits', function (Blueprint $table) {
            $table->string('direction', 10)->default('in');

            $table->dropIndex(['exited_at']);
            $table->dropIndex(['status', 'entered_at']);
            $table->dropIndex(['entered_at']);
            $table->dropIndex(['customer_vehicle_id', 'entered_at']);

            foreach (['entry_gate_id', 'parking_slot_id', 'exit_gate_id', 'delivered_by_id', 'exit_by_id', 'job_card_id'] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }

            $table->dropColumn(['exited_at', 'outward_type', 'driver_type', 'status']);
        });

        Schema::table('gate_visits', function (Blueprint $table) {
            $table->renameColumn('entered_at', 'gated_at');
        });

        Schema::rename('gate_visits', 'gate_events');

        Schema::table('gate_events', function (Blueprint $table) {
            $table->index('gated_at');
            $table->index(['direction', 'gated_at']);
            $table->index(['customer_vehicle_id', 'gated_at']);
        });
    }
};
