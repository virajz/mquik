<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 37 — Advance Payment Entry.
 *
 * Cashier / accounts records an advance PAYMENT made to a vendor (the money-out
 * mirror of module 26 AdvanceReceipt). Uses its OWN FY-based series
 * `MQ/AP/26-27/#####` via `fy_label`. Reuses the payment-mode / bank /
 * cheque-bounce masters; links to the module-36 advance request and the VPI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->nullable()->unique();
            $table->string('fy_label', 10)->nullable(); // "26-27" — per-FY sequence anchor

            $table->foreignId('vendor_advance_request_id')->nullable()->constrained('vendor_advance_requests')->nullOnDelete();
            $table->foreignId('vendor_purchase_inquiry_id')->nullable()->constrained('vendor_purchase_inquiries')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->foreignId('entry_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();

            $table->string('advance_payment_type', 30)->nullable(); // against_request / direct / odd_item
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference_no')->nullable();   // UTR / txn reference
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable();     // issued / cleared / returned_bounced / cancelled
            $table->string('payment_status', 20)->default('posted'); // posted / cancelled / reversed
            $table->string('reversal_reason', 40)->nullable();
            $table->string('cancellation_reason', 40)->nullable();

            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('fy_label');
            $table->index('vendor_id');
        });

        Schema::create('advance_payment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_payment_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // cheque_copy / utr_screenshot / deposit_slip / payment_advice
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['advance_payment_id', 'sequence_no'], 'ap_attachments_payment_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payment_attachments');
        Schema::dropIfExists('advance_payments');
    }
};
