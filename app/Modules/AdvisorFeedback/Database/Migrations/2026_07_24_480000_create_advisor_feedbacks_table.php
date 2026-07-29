<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 73 — Advisor Feedback / Advisor Rating.
 *
 * The reverse of customer feedback: the service advisor rates the CUSTOMER
 * (cooperation, timely approvals, payment as committed, professionalism, and
 * whether they'd handle them again) after a job. Header only — five 1–5 rating
 * answers and a sent → submitted lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('feedback_no')->nullable()->unique();

            $table->string('status', 12)->default('pending'); // pending / submitted / cancelled

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('gate_pass_approval_id')->nullable()->constrained('gate_pass_approvals')->nullOnDelete();

            $table->string('invoice_reference')->nullable();

            // Rating answers (1–5) — about the customer.
            $table->unsignedTinyInteger('cooperative_rating')->nullable();
            $table->unsignedTinyInteger('timely_approvals_rating')->nullable();
            $table->unsignedTinyInteger('payment_committed_rating')->nullable();
            $table->unsignedTinyInteger('professional_rating')->nullable();
            $table->unsignedTinyInteger('prefer_again_rating')->nullable();

            $table->dateTime('submitted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('advisor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_feedbacks');
    }
};
