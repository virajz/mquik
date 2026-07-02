<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no', 32)->nullable()->unique();          // MQ/RF/26-27/00001
            $table->string('fy_label', 7)->nullable();                      // 26-27
            $table->string('refund_against', 20)->default('regular_receipt'); // advance_receipt | regular_receipt
            $table->string('refund_status', 20)->default('requested');      // requested | on_hold | refunded | rejected | cancelled

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('refund_type_id')->nullable()->constrained('refund_types')->nullOnDelete();

            // Reference documents (all optional).
            $table->foreignId('advance_receipt_id')->nullable()->constrained('regular_receipts')->nullOnDelete();
            $table->foreignId('regular_receipt_id')->nullable()->constrained('regular_receipts')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('regular_sales_invoice_id')->nullable()->constrained('regular_sales_invoices')->nullOnDelete();
            $table->foreignId('sales_return_id')->nullable()->constrained('sales_returns')->nullOnDelete(); // credit note

            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('refunded_by_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->decimal('amount', 14, 2)->default(0);
            $table->foreignId('refund_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();

            $table->string('cheque_no', 40)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable();                // issued | cleared | bounced | cancelled
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();

            $table->foreignId('cancellation_reason_id')->nullable()->constrained('receipt_cancellation_reasons')->nullOnDelete();
            $table->string('reference_no', 60)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['refund_status', 'created_at']);
            $table->index(['customer_id', 'refund_status']);
            $table->index(['fy_label']);
        });

        Schema::create('receipt_refund_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_refund_id')->constrained('receipt_refunds')->cascadeOnDelete();
            $table->string('attachment_type', 25)->nullable();              // customer_request_proof | cheque_copy | utr_screenshot | payment_advice
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_refund_attachments');
        Schema::dropIfExists('receipt_refunds');
    }
};
