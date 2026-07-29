<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 26 — Advance Receipt Entry.
 *
 * Cashier manually records an advance payment against a job card / estimate.
 * Uses an FY-based series `MQ/AR/26-27/#####` (its OWN sequence via `fy_label`,
 * deliberately NOT the `MQ/RR/` prefix used by RegularReceipt, to avoid sharing
 * that module's counter). Reuses all payment/bank/cheque/receipt reason masters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->nullable()->unique();
            $table->string('fy_label', 10)->nullable(); // "26-27" — per-FY sequence anchor

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('advance_receipt_request_id')->nullable()->constrained('advance_receipt_requests')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // received by

            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('receipt_cancellation_reasons')->nullOnDelete();
            $table->foreignId('difference_reason_id')->nullable()->constrained('receipt_difference_reasons')->nullOnDelete();

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('difference_amount', 12, 2)->nullable();
            $table->string('payment_status', 20)->default('fully_received'); // partially_received / fully_received / cancelled / failed / refunded
            $table->string('reference_no')->nullable();   // UTR / txn reference
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable(); // received / deposited / cleared / returned / cancelled

            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('fy_label');
            $table->index('job_card_id');
        });

        Schema::create('advance_receipt_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // cheque_copy / utr_screenshot / deposit_slip / payment_advice
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['advance_receipt_id', 'sequence_no'], 'ar_attachments_receipt_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_receipt_attachments');
        Schema::dropIfExists('advance_receipts');
    }
};
