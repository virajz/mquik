<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The drop leg already carries a region; the pickup leg only had free text.
 * A collection address needs the same state/city/area breakdown — that is what
 * routes the driver and what any pickup-load report groups by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('pickup_region_id')->nullable()->after('pickup_address_id')->constrained('regions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_region_id');
        });
    }
};
