<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Redo the appointment backfill in the direction the workshop actually works.
 *
 * The previous migration walked `gate_visits.job_card_id`, a reverse pointer
 * nothing populates. The real order is inward first, job card second — a job
 * card cannot be raised for a car that has not arrived, which is why
 * `job_cards.gate_event_id` is a required field. So the booking is recovered by
 * walking that link back: card knows its inward and its appointment, therefore
 * the inward's appointment is the card's.
 *
 * Only fills rows that are still null; anything already linked by hand wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            update gate_visits
            set appointment_id = (
                select job_cards.appointment_id
                from job_cards
                where job_cards.gate_event_id = gate_visits.id
                  and job_cards.appointment_id is not null
                order by job_cards.id
                limit 1
            )
            where gate_visits.appointment_id is null
        ');
    }

    public function down(): void
    {
        // Nothing to undo: the column itself is dropped by the migration that
        // added it, and un-backfilling would discard hand-made links too.
    }
};
