<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 66 — Gate Pass Approval Request / Response.
 *
 * When a vehicle must be delivered against outstanding (partial / full credit,
 * post-dated cheque, or without an insurance DO), the advisor raises a gate-pass
 * approval. The system captures invoice / receipt / outstanding / credit-exposure
 * amounts, routes to the approval authority by the amount matrix (≤ 25k advisor,
 * > 25k admin), and records the request → approved / rejected / cancelled
 * lifecycle. Header + document attachments (no line items).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_pass_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->string('priority', 10)->default('normal');    // normal / high / emergency
            $table->string('credit_type', 30)->nullable();
            $table->string('credit_reason', 40)->nullable();
            $table->string('customer_commitment', 20)->nullable(); // verbal / written
            $table->string('security_deposit', 10)->nullable();    // cheque / cash
            $table->string('risk_type', 10)->nullable();           // low / medium / high
            $table->string('risk_category', 30)->nullable();
            $table->string('approval_authority', 30)->nullable();  // service_advisor_cashier / admin_hr_owner
            $table->string('status', 30)->default('requested');
            $table->string('cancellation_reason', 30)->nullable();

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('invoice_reference')->nullable();
            $table->string('po_reference')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->string('outstanding_reference')->nullable();

            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->decimal('receipt_amount', 12, 2)->nullable();
            $table->decimal('outstanding_amount', 12, 2)->nullable();
            $table->decimal('credit_exposure', 12, 2)->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
            $table->index('created_at');
        });

        Schema::create('gate_pass_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_pass_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // customer_request_letter / customer_request_form / approval_note / payment_commitment / post_dated_cheque
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['gate_pass_approval_id', 'sequence_no'], 'gpa_attachments_approval_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_pass_approval_attachments');
        Schema::dropIfExists('gate_pass_approvals');
    }
};
