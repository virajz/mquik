<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A both-legs booking wants both windows up front: the pickup job runs in its
 * own slot, and the return trip's slot is captured now so the drop job created
 * later inherits it instead of re-asking the customer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->foreignId('drop_time_slot_id')->nullable()->after('time_slot_id')->constrained('time_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drop_time_slot_id');
        });
    }
};
