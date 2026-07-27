<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 20 — Claim Intimation.
 *
 * Register an accidental insurance claim against a job card: policy details,
 * claim type, damage nature, how/when the insurer was intimated, and the
 * survey turnaround so a surveyor can be notified. Policies aren't first-class
 * records in this system, so the policy is captured as a number + type (same
 * as sales_estimates).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_intimations', function (Blueprint $table) {
            $table->id();
            $table->string('intimation_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('insurance_policy_type_id')->nullable()->constrained('insurance_policy_types')->nullOnDelete();
            $table->string('policy_no')->nullable();
            $table->foreignId('claim_type_id')->nullable()->constrained('claim_types')->nullOnDelete();
            $table->string('claim_no')->nullable(); // insurer's claim reference, once intimated

            $table->string('damage_nature', 30)->nullable(); // accident / fire / theft / natural_calamity / other
            $table->string('intimation_mode', 30)->nullable(); // email / insurance_portal / api / phone / mobile_app
            $table->string('status', 20)->default('pending'); // pending / intimated / cancelled
            $table->string('pending_reason', 30)->nullable(); // policy_expired / delay_in_intimation
            $table->string('survey_tat', 20)->nullable(); // within_24h / within_48h / within_72h

            $table->dateTime('intimated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['insurance_company_id', 'status']);
            $table->index('job_card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_intimations');
    }
};
