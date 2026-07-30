<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 82 — VMS (Visitor Management System).
 *
 * Reception / queue front-desk for walk-in and appointment visits: a printed
 * token, the customer, vehicle, department and advisor assignment, arrival
 * mode, waiting-time bucket and the advisor_assigned → consultation →
 * job_card_created / cancelled lifecycle with per-stage timestamps. Header +
 * token / note attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_visits', function (Blueprint $table) {
            $table->id();
            $table->string('token_no')->nullable()->unique();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('customer_type', 20)->nullable();              // senior_citizen / lady_customer / gents_customer
            $table->string('visit_purpose', 25)->nullable();             // periodic_maintenance / general_repair / ...
            $table->string('arrival_mode', 15)->nullable();              // walk_in / appointment / phone_call / ...
            $table->string('advisor_assignment_method', 12)->nullable(); // auto / manual / least_busy / preferred
            $table->string('advisor_availability', 12)->nullable();      // available / busy / on_break / ...
            $table->string('waiting_time_category', 8)->nullable();      // lt_10 / 10_20 / ... / gt_60

            $table->string('status', 25)->default('advisor_assigned');   // advisor_assigned / consultation_started / ...
            $table->string('delay_reason', 20)->nullable();              // advisor_busy / workload_high / ...
            $table->string('no_show_reason', 20)->nullable();            // customer_left / customer_cancelled

            $table->string('announcement_message')->nullable();
            $table->text('notes')->nullable();

            $table->dateTime('arrival_at')->nullable();
            $table->dateTime('advisor_assigned_at')->nullable();
            $table->dateTime('consultation_started_at')->nullable();
            $table->dateTime('consultation_ended_at')->nullable();
            $table->dateTime('job_card_created_at')->nullable();
            $table->dateTime('exit_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('arrival_at');
        });

        Schema::create('visitor_visit_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_visit_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // token_slip / customer_note / visit_note
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['visitor_visit_id', 'sequence_no'], 'visitor_attachments_visit_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_visit_attachments');
        Schema::dropIfExists('visitor_visits');
    }
};
