<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The trip's own history: who was put on it and when, when they left, when they
 * reached the address, and when the car actually changed hands. Status stops
 * being typed and derives from these facts instead — the same doctrine the
 * Appointment module already follows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('vendor_courier_id');
            $table->foreignId('assigned_by_user_id')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
            $table->timestamp('departed_at')->nullable()->after('assigned_by_user_id');
            $table->timestamp('reached_at')->nullable()->after('departed_at');
            $table->timestamp('collected_at')->nullable()->after('reached_at');
            $table->timestamp('delivered_at')->nullable()->after('collected_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason_id');
        });

        // Existing cancellations keep their meaning under the derived rule.
        DB::table('pickup_drops')->where('status', 'cancelled')->whereNull('cancelled_at')
            ->update(['cancelled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by_user_id');
            $table->dropColumn(['assigned_at', 'departed_at', 'reached_at', 'collected_at', 'delivered_at', 'cancelled_at']);
        });
    }
};
