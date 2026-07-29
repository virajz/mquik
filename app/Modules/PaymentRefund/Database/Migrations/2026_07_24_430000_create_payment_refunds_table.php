<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 65 — Payment Refund Request / Response.
 *
 * A vendor refunds money back to the workshop — excess / duplicate payment,
 * cancelled PO, invoice revision, or a spares / outside-labour purchase return.
 * Records the refund against its source references, the mode / bank / cheque,
 * and the request → refunded / rejected / cancelled lifecycle. Header +
 * document attachments (no line items).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no')->nullable()->unique();

            $table->string('refund_against', 20)->nullable(); // advance_payment / regular_payment
            $table->string('refund_type', 30)->nullable();
            $table->string('priority', 10)->default('normal'); // normal / high / urgent
            $table->string('status', 20)->default('requested'); // requested / on_hold / refunded / rejected / cancelled

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('refund_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->foreignId('advance_payment_id')->nullable()->constrained('advance_payments')->nullOnDelete();
            $table->foreignId('vendor_purchase_order_id')->nullable()->constrained('vendor_purchase_orders')->nullOnDelete();
            $table->foreignId('goods_return_note_id')->nullable()->constrained('goods_return_notes')->nullOnDelete();
            $table->foreignId('outside_labour_return_id')->nullable()->constrained('outside_labour_returns')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('regular_payment_reference')->nullable();
            $table->string('purchase_invoice_reference')->nullable();

            $table->string('refund_mode', 20)->nullable(); // cash / cheque / neft / rtgs / imps / upi / bank_transfer
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('reference_no')->nullable(); // UTR / txn reference
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable(); // cleared / bounce
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();

            $table->string('rejection_reason', 30)->nullable();
            $table->string('hold_reason', 30)->nullable();
            $table->string('cancellation_reason', 30)->nullable();

            $table->decimal('amount', 12, 2)->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->dateTime('rejected_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('payment_refund_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_refund_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // excess_payment_proof / cheque_copy / utr_screenshot / payment_advice
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['payment_refund_id', 'sequence_no'], 'pr_attachments_refund_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refund_attachments');
        Schema::dropIfExists('payment_refunds');
    }
};
