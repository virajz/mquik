<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen the job-card history stream into a vehicle timeline.
 *
 * Two changes make that possible:
 *  - `customer_vehicle_id` lets events be read per vehicle across every job
 *    card it has ever had, and is backfilled from the existing cards.
 *  - `job_card_id` becomes nullable so the parts of a visit that happen before
 *    a card exists (appointment booked, vehicle arrived at the gate) can still
 *    land on the vehicle's timeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_card_history_events', function (Blueprint $table) {
            $table->foreignId('customer_vehicle_id')->nullable()->after('job_card_id')
                ->constrained('customer_vehicles')->nullOnDelete();

            $table->index(['customer_vehicle_id', 'occurred_at']);
        });

        // Existing events all belong to a card — inherit that card's vehicle.
        DB::table('job_card_history_events')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $vehicleId = DB::table('job_cards')->where('id', $row->job_card_id)->value('customer_vehicle_id');
                if ($vehicleId) {
                    DB::table('job_card_history_events')->where('id', $row->id)->update(['customer_vehicle_id' => $vehicleId]);
                }
            }
        });

        // Drop the NOT NULL so pre-job-card events can be recorded.
        Schema::table('job_card_history_events', function (Blueprint $table) {
            $table->foreignId('job_card_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_card_history_events', function (Blueprint $table) {
            $table->dropIndex(['customer_vehicle_id', 'occurred_at']);
            $table->dropConstrainedForeignId('customer_vehicle_id');
        });

        DB::table('job_card_history_events')->whereNull('job_card_id')->delete();

        Schema::table('job_card_history_events', function (Blueprint $table) {
            $table->foreignId('job_card_id')->nullable(false)->change();
        });
    }
};
