<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A separate slot for the drop leg.
 *
 * `time_slot_id` was a single booking-wide slot, but a pickup and a drop are two
 * journeys at two different times — "collect at 9, return at 6" cannot be said
 * with one field. The existing column keeps its data and becomes the pickup
 * slot; this is the other half.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('drop_time_slot_id')->nullable()->after('time_slot_id')
                ->constrained('time_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drop_time_slot_id');
        });
    }
};
