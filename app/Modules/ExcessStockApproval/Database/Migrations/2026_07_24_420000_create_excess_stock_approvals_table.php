<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 60 — Excess Stock Approval Request / Response.
 *
 * The store requests approval to return or write off excess / dead stock
 * (wrong or excess purchase, cancelled order, non-returnable, expired warranty,
 * open packing, etc.); admin approves or rejects. Header + item-wise spare
 * lines (qty / rate carry the excess value) + document attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excess_stock_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->nullable()->unique();

            $table->string('excess_stock_reason', 30)->nullable();
            $table->string('vendor_rejection_reason', 30)->nullable(); // fitment_issue / damaged_at_workshop
            $table->string('priority', 10)->default('normal');          // normal / high / urgent
            $table->string('status', 30)->default('requested');         // requested / on_hold / under_review / verbal_clarification / approved / rejected

            $table->foreignId('goods_handover_id')->nullable()->constrained('goods_handovers')->nullOnDelete(); // technician parts return ref
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();   // GRN ref
            $table->string('purchase_invoice_reference')->nullable();

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_executive_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('mistake_by_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('excess_stock_reason');
            $table->index('created_at');
        });

        Schema::create('excess_stock_approval_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('excess_stock_approval_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['excess_stock_approval_id', 'sequence_no'], 'esa_items_request_sequence_index');
        });

        Schema::create('excess_stock_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('excess_stock_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // purchase_invoice_copy / damaged_proof / vendor_rejection_proof
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['excess_stock_approval_id', 'sequence_no'], 'esa_attachments_request_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excess_stock_approval_attachments');
        Schema::dropIfExists('excess_stock_approval_items');
        Schema::dropIfExists('excess_stock_approvals');
    }
};
