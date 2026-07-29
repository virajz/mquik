<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 40 — VPO (Vendor Purchase Order) Cancel Request / Response.
 *
 * The store asks a vendor to cancel a purchase order (full / partial / qty
 * reduction) and tracks the vendor's response, cancellation charge terms,
 * advance-refund handling and dispatch-stage rejection. Header + item-wise
 * cancellation lines (each pointing at the PO / job card / spare) + document
 * attachments (dispatch challan / courier / transport / invoice / refund).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpo_cancel_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->nullable()->unique();

            $table->string('cancellation_request_type', 30)->nullable(); // full / partial_item / quantity_reduction
            $table->string('cancellation_reason', 40)->nullable();
            $table->string('vendor_category', 20)->nullable();

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('vendor_purchase_order_id')->nullable()->constrained('vendor_purchase_orders')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('status', 30)->default('requested_to_vendor'); // requested_to_vendor / vendor_reviewing / vendor_accepted / vendor_rejected / partially_accepted / fully_accepted / cancelled
            $table->string('cancellation_term', 20)->nullable();   // no_charge / fixed_charge / percentage_charge
            $table->decimal('cancellation_charge', 12, 2)->nullable();
            $table->string('advance_payment_status', 30)->nullable(); // no_advance / advance_paid / refund_requested / refund_adjusted / refund_next_order / refund_received
            $table->string('hold_reason', 40)->nullable();
            $table->string('rejection_reason', 40)->nullable();
            $table->string('vendor_rating_type', 20)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('advance_payment_status');
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('vpo_cancel_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpo_cancel_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_purchase_order_id')->nullable()->constrained('vendor_purchase_orders')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('quantity_to_cancel', 12, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vpo_cancel_request_id', 'sequence_no'], 'vcr_items_request_sequence_index');
        });

        Schema::create('vpo_cancel_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpo_cancel_request_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // dispatch_challan / courier_receipt / transport_receipt / invoice_copy / refund_receipt
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vpo_cancel_request_id', 'sequence_no'], 'vcr_attachments_request_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpo_cancel_request_attachments');
        Schema::dropIfExists('vpo_cancel_request_items');
        Schema::dropIfExists('vpo_cancel_requests');
    }
};
