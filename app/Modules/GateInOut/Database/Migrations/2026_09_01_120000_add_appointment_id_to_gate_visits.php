<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give the gate a real reference to the booking it is fulfilling.
 *
 * Until now an appointment reached an inward only by guessing: same vehicle,
 * entered any time after the booking was created. With no upper bound that
 * matched visits weeks apart — a booking for 03/08 was reading "Arrived" off a
 * gate entry on 19/08 — so arrival was a heuristic where it should be a fact.
 *
 * Existing rows backfill only through the job card, which is already a real
 * link on both sides. Anything the job card cannot vouch for is left null
 * rather than guessed at a second time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gate_visits', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('customer_id')
                ->constrained('appointments')->nullOnDelete();
        });

        DB::statement('
            update gate_visits
            set appointment_id = (
                select job_cards.appointment_id
                from job_cards
                where job_cards.id = gate_visits.job_card_id
            )
            where gate_visits.job_card_id is not null
        ');
    }

    public function down(): void
    {
        Schema::table('gate_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
