<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regular_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 32)->nullable()->unique();  // MQ/PV/26-27/00001
            $table->string('fy_label', 7)->nullable();               // 26-27
            $table->string('status', 30)->default('draft');          // draft | on_hold | paid | cancelled

            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('paid_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advance_payment_id')->nullable()->constrained('regular_payments')->nullOnDelete();
            $table->foreignId('purchase_entry_id')->nullable()->constrained('purchase_entries')->nullOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();

            $table->decimal('amount', 14, 2)->default(0);

            $table->string('cheque_no', 40)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_status', 20)->nullable();          // issued | cleared | bounced | cancelled
            $table->foreignId('cheque_bounce_reason_id')->nullable()->constrained('cheque_bounce_reasons')->nullOnDelete();

            $table->foreignId('payment_hold_reason_id')->nullable()->constrained('payment_hold_reasons')->nullOnDelete();
            $table->foreignId('payment_cancellation_reason_id')->nullable()->constrained('payment_cancellation_reasons')->nullOnDelete();

            $table->string('reference_no', 60)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['vendor_id', 'status']);
            $table->index(['fy_label']);
            $table->index(['cheque_status']);
        });

        Schema::create('regular_payment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regular_payment_id')->constrained('regular_payments')->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable();        // cheque_copy | utr_screenshot | payment_advice
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regular_payment_attachments');
        Schema::dropIfExists('regular_payments');
    }
};
