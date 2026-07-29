<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 24 — Jobcard Cancel Approval.
 *
 * Admin approval workflow for cancelling a job card: cancellation type/reason,
 * a 4-level approval hierarchy, the downstream impacts (part return, stock
 * reversal, refund, …), and refund status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_cancel_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete(); // "job type"
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('cancel_reason_id')->nullable()->constrained('job_card_cancel_reasons')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('cancellation_type', 30)->nullable();  // wrong_entry / customer_rejection / insurance_rejection / duplicate / internal_error / operational_issue
            $table->string('approval_level', 20)->nullable();     // l1_advisor / l2_store / l3_accounts / l4_workshop
            $table->string('status', 20)->default('pending');     // pending / requested / under_review / approved / rejected / cancelled / reversed
            $table->json('impacts')->nullable();                  // multi-select impact keys
            $table->string('approval_rejection_reason', 30)->nullable(); // insufficient_reason / financial_impact / work_completed / management_decision
            $table->string('refund_status', 20)->nullable();      // pending / processed / completed

            $table->dateTime('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('job_card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_cancel_approvals');
    }
};
