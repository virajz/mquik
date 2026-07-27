<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Additional Work Performed" — a scope line discovered/performed AFTER the
 * inspection started, beyond the originally-booked work. Flagged per line so
 * reports can separate original vs additional labour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->boolean('is_additional')->default(false)->after('service_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_inspection_order_scopes', function (Blueprint $table) {
            $table->dropColumn('is_additional');
        });
    }
};
