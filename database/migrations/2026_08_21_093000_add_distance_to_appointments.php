<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trip distance for a pickup/drop booking, mirroring the columns the PickupDrop
 * job already carries. Captured at booking so the coordinator can quote the
 * collection charge on the call, and so the eventual PickupDrop does not have to
 * ask the same question again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('distance_km', 8, 2)->nullable()->after('drop_time_slot_id');
            $table->foreignId('distance_slab_id')->nullable()->after('distance_km')->constrained('distance_slabs')->nullOnDelete();
            $table->decimal('distance_charge', 10, 2)->nullable()->after('distance_slab_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distance_slab_id');
            $table->dropColumn(['distance_km', 'distance_charge']);
        });
    }
};
