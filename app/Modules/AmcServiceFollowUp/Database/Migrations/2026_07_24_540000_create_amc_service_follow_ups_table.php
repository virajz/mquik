<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 81 — AMC Service Due / Renewal Follow-Ups.
 *
 * Reminders and tracking for AMC due services and AMC renewals: the AMC
 * reference, due date / interval, follow-up attempts and customer response,
 * escalation, retention and the → converted / overdue / lost lifecycle. Header
 * + note attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amc_service_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('follow_up_no')->nullable()->unique();

            $table->string('follow_up_type', 15)->default('service_due'); // service_due / amc_renewal
            $table->foreignId('vehicle_amc_id')->nullable()->constrained('vehicle_amcs')->nullOnDelete();
            $table->foreignId('follow_up_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('vehicle_history_reference')->nullable();
            $table->string('estimate_template_reference')->nullable();
            $table->string('price_list_reference')->nullable();
            $table->string('package_reference')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('follow_up_attempt', 10)->nullable();
            $table->string('follow_up_mode', 20)->nullable();
            $table->string('customer_response', 30)->nullable();
            $table->string('customer_satisfaction', 15)->nullable();
            $table->string('lost_reason', 30)->nullable();
            $table->string('missed_service_reason', 30)->nullable();
            $table->string('escalation', 20)->nullable();
            $table->string('escalation_reason', 20)->nullable();
            $table->string('customer_retention', 12)->nullable(); // active / lost / recovered

            $table->string('service_interval_method', 15)->nullable(); // kilometer / date
            $table->string('service_interval', 20)->nullable();
            $table->string('reminder_frequency', 20)->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('odometer')->nullable();

            $table->dateTime('due_generated_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->dateTime('attempt_at')->nullable();
            $table->dateTime('response_at')->nullable();
            $table->dateTime('appointment_at')->nullable();
            $table->dateTime('arrival_at')->nullable();
            $table->dateTime('job_card_open_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('follow_up_type');
        });

        Schema::create('amc_service_follow_up_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amc_service_follow_up_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // service_schedule / customer_note / follow_up_notes
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['amc_service_follow_up_id', 'sequence_no'], 'asf_attachments_followup_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amc_service_follow_up_attachments');
        Schema::dropIfExists('amc_service_follow_ups');
    }
};
