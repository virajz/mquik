<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 72 — Customer Feedback / Service Rating / Post-Service Follow-up.
 *
 * Capture post-service ratings (1–5) and feedback, and run the scheduled
 * post-service follow-up (4 / 7 / 15 / 30 days after billing). Header carries
 * the follow-up schedule / attempt, the rating answers, and the sent →
 * satisfied / dissatisfied / resolved lifecycle. Header + a feedback screenshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('feedback_no')->nullable()->unique();

            $table->string('follow_up_schedule', 20)->nullable(); // 4_days / 7_days / 15_days / 30_days / custom
            $table->unsignedSmallInteger('follow_up_custom_days')->nullable();
            $table->string('follow_up_category', 30)->nullable();
            $table->string('follow_up_mode', 20)->nullable();
            $table->string('follow_up_attempt', 10)->nullable();  // first / second / third / final
            $table->string('vehicle_observation', 30)->nullable();
            $table->string('feedback_source', 20)->nullable();
            $table->string('feedback_category', 20)->nullable();  // complaint / suggestion / praise
            $table->string('status', 20)->default('sent');        // sent / satisfied / dissatisfied / under_investigation / resolved / cancelled

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('gate_pass_approval_id')->nullable()->constrained('gate_pass_approvals')->nullOnDelete();

            $table->string('invoice_reference')->nullable();

            // Rating answers (1–5).
            $table->unsignedTinyInteger('staff_experience_rating')->nullable();
            $table->unsignedTinyInteger('service_experience_rating')->nullable();
            $table->unsignedTinyInteger('service_rating')->nullable();
            $table->unsignedTinyInteger('price_rating')->nullable();
            $table->unsignedTinyInteger('ontime_delivery_rating')->nullable();
            $table->boolean('would_recommend')->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('service_rating');
            $table->index('created_at');
        });

        Schema::create('customer_feedback_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_feedback_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // feedback_screenshot
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['customer_feedback_id', 'sequence_no'], 'cf_attachments_feedback_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_feedback_attachments');
        Schema::dropIfExists('customer_feedbacks');
    }
};
