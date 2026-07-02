<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regular_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 32)->nullable()->unique();  // MQ/RR/26-27/00708
            $table->string('fy_label', 7)->nullable();               // 26-27
            $table->string('status', 30)->default('draft');          // draft | confirmed | cancelled

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('regular_sales_invoice_id')->nullable()->constrained('regular_sales_invoices')->nullOnDelete();
            $table->foreignId('counter_sales_invoice_id')->nullable()->constrained('counter_sales_invoices')->nullOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advance_receipt_id')->nullable()->constrained('regular_receipts')->nullOnDelete();

            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('difference_amount', 14, 2)->default(0);
            $table->foreignId('receipt_difference_reason_id')->nullable()->constrained('receipt_difference_reasons')->nullOnDelete();

            $table->string('cheque_no', 40)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable();          // received | deposited | cleared | returned | cancelled
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();

            $table->string('reference_no', 60)->nullable();           // UTR / txn / bill ref
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('receipt_cancellation_reasons')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('cleared_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['fy_label']);
            $table->index(['cheque_status']);
            $table->index(['payment_mode_id']);
        });

        Schema::create('regular_receipt_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regular_receipt_id')->constrained('regular_receipts')->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable();        // cheque_copy | utr_screenshot | deposit_slip | payment_advice
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regular_receipt_attachments');
        Schema::dropIfExists('regular_receipts');
    }
};
