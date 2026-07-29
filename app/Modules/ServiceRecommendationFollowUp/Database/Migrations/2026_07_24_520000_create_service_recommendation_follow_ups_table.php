<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 79 — Service Recommended Follow-Ups.
 *
 * Follow-ups based on technician-recommended future services (raised at invoice /
 * inspection time): the recommendation type / reason / category, the follow-up
 * attempts and customer response, escalation, retention and the → converted /
 * lost lifecycle. Header + note attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_recommendation_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('recommendation_no')->nullable()->unique();

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('follow_up_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('invoice_reference')->nullable();
            $table->string('service_history_reference')->nullable();
            $table->string('estimate_reference')->nullable();
            $table->string('price_list_reference')->nullable();

            $table->string('recommended_service')->nullable(); // free text — e.g. Tyre Replace
            $table->string('recommendation_type', 20)->nullable();
            $table->string('recommendation_reason', 30)->nullable();
            $table->string('recommendation_category', 30)->nullable();
            $table->string('priority', 10)->default('normal'); // normal / medium / high
            $table->string('status', 20)->default('pending');

            $table->string('reminder_frequency', 20)->nullable();
            $table->string('follow_up_attempt', 10)->nullable();
            $table->string('follow_up_mode', 20)->nullable();
            $table->string('customer_response', 30)->nullable();
            $table->string('customer_satisfaction', 15)->nullable();
            $table->string('customer_retention', 12)->nullable();
            $table->string('escalation', 30)->nullable();
            $table->string('escalation_reason', 20)->nullable();
            $table->string('lost_reason', 30)->nullable();

            $table->decimal('estimated_value', 12, 2)->nullable();

            $table->dateTime('recommended_at')->nullable();
            $table->dateTime('informed_at')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('estimate_at')->nullable();
            $table->dateTime('appointment_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('recommendation_category');
            $table->index('created_at');
        });

        Schema::create('service_recommendation_follow_up_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_recommendation_follow_up_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // customer_note / follow_up_notes
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['service_recommendation_follow_up_id', 'sequence_no'], 'srf_attachments_followup_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_recommendation_follow_up_attachments');
        Schema::dropIfExists('service_recommendation_follow_ups');
    }
};
