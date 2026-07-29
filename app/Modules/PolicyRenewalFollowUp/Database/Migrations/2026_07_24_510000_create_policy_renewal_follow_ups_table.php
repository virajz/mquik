<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 77 — Insurance Policy Renewal Follow-Ups.
 *
 * Track follow-ups for upcoming insurance policy renewals: the expiring policy,
 * reminder schedule, follow-up attempts and customer response, escalation,
 * retention and the → renewed / overdue / lost lifecycle. Also covers module 78
 * (Insurance Policy Renewal) — the renewed-policy details / attachments live on
 * this record. Header + document attachments (RC / Aadhar / policy copies …).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_renewal_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('follow_up_no')->nullable()->unique();

            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('insurance_policy_type_id')->nullable()->constrained('insurance_policy_types')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('renewal_reference')->nullable();
            $table->string('policy_number', 80)->nullable();
            $table->date('policy_start_date')->nullable();
            $table->date('policy_end_date')->nullable();

            $table->string('priority', 10)->default('normal'); // normal / medium / high
            $table->string('status', 20)->default('pending');
            $table->string('reminder_frequency', 20)->nullable();
            $table->string('follow_up_attempt', 10)->nullable();
            $table->string('follow_up_mode', 20)->nullable();
            $table->string('customer_response', 30)->nullable();
            $table->string('lost_reason', 30)->nullable();
            $table->string('escalation', 20)->nullable();
            $table->string('escalation_reason', 20)->nullable();
            $table->string('customer_retention', 12)->nullable(); // active / lost / recovered

            $table->decimal('renewal_premium', 12, 2)->nullable();

            $table->dateTime('reminder_at')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('response_at')->nullable();
            $table->dateTime('quote_shared_at')->nullable();
            $table->dateTime('payment_received_at')->nullable();
            $table->dateTime('policy_issued_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('policy_end_date');
            $table->index('customer_retention');
        });

        Schema::create('policy_renewal_follow_up_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_renewal_follow_up_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // rc / aadhar / pan / previous_policy / insurance_quote / renewed_policy / payment_receipt
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['policy_renewal_follow_up_id', 'sequence_no'], 'prf_attachments_followup_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_renewal_follow_up_attachments');
        Schema::dropIfExists('policy_renewal_follow_ups');
    }
};
