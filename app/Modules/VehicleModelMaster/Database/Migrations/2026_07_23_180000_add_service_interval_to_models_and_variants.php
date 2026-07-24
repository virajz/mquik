<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service interval config, so a vehicle's next-service-due can be derived
 * rather than tracked by hand.
 *
 * The interval lives on the model (e.g. Swift = 10,000 km / 12 months). A
 * variant may override it where the drivetrain differs — a diesel often needs a
 * tighter interval than its petrol sibling — but usually inherits the model's.
 *
 * This is configuration only. Nothing here sends a reminder; that belongs to
 * the SMS integration work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->unsignedInteger('service_interval_km')->nullable()->after('vehicle_segment_id');
            $table->unsignedSmallInteger('service_interval_months')->nullable()->after('service_interval_km');
        });

        Schema::table('vehicle_variants', function (Blueprint $table) {
            // Null = inherit the model's interval.
            $table->unsignedInteger('service_interval_km')->nullable()->after('year');
            $table->unsignedSmallInteger('service_interval_months')->nullable()->after('service_interval_km');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->dropColumn(['service_interval_km', 'service_interval_months']);
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn(['service_interval_km', 'service_interval_months']);
        });
    }
};
