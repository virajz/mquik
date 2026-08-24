<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status is now derived from what has actually happened to the vehicle, so it
 * can no longer carry a decision nobody can observe. Cancelling is exactly such
 * a decision, and it moves to its own timestamp — the derivation reads that flag
 * rather than a status somebody typed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason_id');
        });

        // Existing cancellations keep their meaning under the new rule.
        DB::table('appointments')
            ->where('status', 'cancelled')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
