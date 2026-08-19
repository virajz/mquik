<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How often each service is actually due.
 *
 * The service-history panel used one flat "older than a year" rule, which is
 * both wrong and useless: a car wash is never overdue, while an oil change at
 * 19,000 km is, regardless of the date. Intervals are per service and workshop
 * staff edit them, so the rule changes without a deploy.
 *
 * Either bound may be left blank — some jobs are time-based, some distance-based,
 * and a service with neither is simply never flagged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_interval_masters', function (Blueprint $table) {
            $table->id();
            // Matched against the service text on past visits, so it is unique
            // and stored uppercase like every other name in the system.
            $table->string('name')->unique();
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->unsignedInteger('interval_km')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_interval_masters');
    }
};
