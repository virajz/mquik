<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The MQUIK Tyre Report — a per-visit inspection of all five wheels.
 *
 * Unlike the 360° checklist (which fits the OK/IA/FA inspection-item model),
 * a tyre carries per-position measurements — size, pressure, tread depth,
 * make, pattern, manufacturing date — so it gets its own structure: one parent
 * report and a fixed set of five position lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tyre_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_no', 32)->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('inspected_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->date('reported_on')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('reported_on');
            $table->index(['customer_vehicle_id', 'reported_on']);
        });

        Schema::create('tyre_report_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tyre_report_id')->constrained('tyre_reports')->cascadeOnDelete();
            $table->string('position', 12); // front_left | front_right | rear_left | rear_right | spare

            // Size as printed on the sidewall, e.g. "205/45 R16 83 V".
            $table->string('tyre_size', 32)->nullable();
            $table->string('tyre_make', 60)->nullable();
            $table->string('pattern', 60)->nullable();
            $table->string('mfg_week_year', 8)->nullable(); // DOT week/year, e.g. "2416"
            $table->decimal('pressure_psi', 5, 1)->nullable();
            $table->decimal('tread_depth_mm', 4, 1)->nullable();

            $table->string('condition', 10)->default('ok'); // ok | repair | replace
            $table->boolean('has_crack')->default(false);
            $table->boolean('has_bulge')->default(false);
            $table->boolean('is_worn_out')->default(false);
            $table->boolean('has_puncture')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->unique(['tyre_report_id', 'position']);
            $table->index(['tyre_report_id', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tyre_report_lines');
        Schema::dropIfExists('tyre_reports');
    }
};
