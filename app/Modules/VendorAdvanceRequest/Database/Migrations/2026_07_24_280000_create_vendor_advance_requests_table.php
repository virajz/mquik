<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 36 — Vendor Advance Payment Request / Response.
 *
 * Request an advance to a vendor for special orders (admin approval): reason,
 * accepted payment mode, bank, a document checklist, and hold/rejection
 * handling with reminders. No money is moved here — that's module 37.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_advance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('prepared_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('verified_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('vendor_purchase_inquiry_id')->nullable()->constrained('vendor_purchase_inquiries')->nullOnDelete();
            $table->foreignId('vpo_approval_id')->nullable()->constrained('vpo_approvals')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('parts_category', 20)->nullable();  // genuine / aftermarket
            $table->string('advance_reason', 30)->nullable();
            $table->string('payment_mode', 20)->nullable();    // cash / bank_transfer / neft / rtgs / imps / upi / cheque
            $table->string('vendor_category', 20)->nullable(); // preferred / approved / backup
            $table->string('status', 20)->default('requested');
            $table->string('hold_reason', 30)->nullable();
            $table->string('rejection_reason', 30)->nullable();
            $table->string('reminder_frequency', 20)->nullable();
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();
            $table->decimal('amount', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('vendor_advance_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_advance_request_id')->constrained()->cascadeOnDelete();
            $table->string('document_name');
            $table->boolean('is_provided')->default(false);
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_advance_request_id', 'sequence_no'], 'var_docs_seq_index');
        });

        Schema::create('vendor_advance_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_advance_request_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // cheque_copy / utr_screenshot / deposit_slip / payment_advice
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_advance_request_id', 'sequence_no'], 'var_attachments_seq_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_advance_request_attachments');
        Schema::dropIfExists('vendor_advance_request_documents');
        Schema::dropIfExists('vendor_advance_requests');
    }
};
