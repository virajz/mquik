<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 74 — Service Due Follow-Ups.
 *
 * Track upcoming scheduled-service due follow-ups per vehicle: the due date /
 * interval, the follow-up attempts and customer response, escalation, retention
 * and the conversion → job-card lifecycle, plus recommended-service references.
 * Header + note attachments. Due-date auto-calculation and auto-reminders are
 * config / scheduled-job concerns (see the design note) — the record captures
 * the data and status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_due_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('follow_up_no')->nullable()->unique();

            $table->foreignId('follow_up_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('service_history_reference')->nullable();
            $table->string('estimate_template_reference')->nullable();
            $table->string('price_list_reference')->nullable();
            $table->string('recommended_service_reference')->nullable(); // AMC / combo / package

            $table->string('status', 20)->default('pending');
            $table->string('follow_up_attempt', 10)->nullable(); // first / second / third / final
            $table->string('follow_up_mode', 20)->nullable();
            $table->string('customer_response', 30)->nullable();
            $table->string('customer_satisfaction', 15)->nullable(); // satisfied / dissatisfied
            $table->string('lost_reason', 30)->nullable();
            $table->string('escalation', 20)->nullable();
            $table->string('escalation_reason', 30)->nullable();
            $table->string('customer_retention', 12)->nullable(); // active / lost / recovered

            $table->string('service_interval_method', 15)->nullable(); // kilometer / date
            $table->string('service_interval', 20)->nullable();        // 10000 / 15000 / 6_month …
            $table->string('reminder_frequency', 20)->nullable();      // before_15_days / before_7_days / today / after_1_week
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
            $table->index('customer_retention');
        });

        Schema::create('service_due_follow_up_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_due_follow_up_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // customer_note / follow_up_notes
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['service_due_follow_up_id', 'sequence_no'], 'sdf_attachments_followup_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_due_follow_up_attachments');
        Schema::dropIfExists('service_due_follow_ups');
    }
};
