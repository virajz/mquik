<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 38 — VPO (Vendor Purchase Order), with the vendor's response (VPR /
 * module 39) folded in as acknowledgement + dispatch fields.
 *
 * The confirmed order placed on a vendor after an inquiry/approval: header +
 * ordered lines (rate, discount, warranty, tax), additional charges, and
 * documents (Dispatch Copy / Invoice Copy). The vendor's acknowledgement
 * (Pending / Accepted / Rejected) and dispatch details (courier, consignment)
 * live on the header — that is module 39 (VPR), no separate module needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->nullable()->unique();

            $table->string('po_type', 30)->nullable(); // against_job_card / stock_replenishment / expense / odd_item
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('vendor_type_id')->nullable()->constrained('vendor_types')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('vendor_purchase_inquiry_id')->nullable()->constrained('vendor_purchase_inquiries')->nullOnDelete();
            $table->foreignId('vpo_approval_id')->nullable()->constrained('vpo_approvals')->nullOnDelete();
            $table->foreignId('advance_payment_id')->nullable()->constrained('advance_payments')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained('vendors')->nullOnDelete(); // logistics vendor
            $table->foreignId('cancellation_reason_id')->nullable()->constrained('estimate_revision_reasons')->nullOnDelete();

            $table->string('vendor_category', 20)->nullable();     // preferred / approved / backup
            $table->string('vendor_rating_type', 20)->nullable();  // quality / price / delivery / support
            $table->string('payment_term', 20)->nullable();        // advance / cod / credit_7 / credit_15 / credit_30
            $table->string('delivery_mode', 20)->nullable();       // pickup / courier / transport / hand_delivery
            $table->string('delivery_commitment', 20)->nullable(); // immediate / same_day / next_day / two_three_days / custom
            $table->unsignedSmallInteger('delivery_custom_days')->nullable();
            $table->date('expected_delivery_date')->nullable();

            $table->string('status', 20)->default('pending'); // pending / acknowledged / dispatched / delivered / cancelled
            $table->string('rejection_reason', 40)->nullable();
            $table->string('cancellation_note', 255)->nullable();

            // Vendor response (module 39 — VPR) folded in.
            $table->string('acknowledgement_status', 20)->default('pending'); // pending / accepted / rejected
            $table->string('courier_company', 120)->nullable();
            $table->string('consignment_no', 80)->nullable();
            $table->date('consignment_date')->nullable();

            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('acknowledgement_status');
            $table->index(['vendor_id', 'status']);
            $table->index('job_card_id');
        });

        Schema::create('vendor_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('part_type_id')->nullable()->constrained('part_types')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->string('discount_type', 20)->nullable(); // line / scheme / cash
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('warranty_type', 20)->nullable(); // no_warranty / vendor / manufacturer
            $table->unsignedSmallInteger('warranty_period_value')->nullable();
            $table->string('warranty_period_unit', 10)->nullable(); // month / day
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->decimal('closing_stock', 12, 2)->nullable(); // reference — on-hand at order time

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_order_id', 'sequence_no'], 'vpo_items_order_sequence_index');
        });

        Schema::create('vendor_purchase_order_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_order_id', 'sequence_no'], 'vpo_charges_order_sequence_index');
        });

        Schema::create('vendor_purchase_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // po_copy / dispatch_copy / invoice_copy / photo_evidence
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vendor_purchase_order_id', 'sequence_no'], 'vpo_attachments_order_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_purchase_order_attachments');
        Schema::dropIfExists('vendor_purchase_order_charges');
        Schema::dropIfExists('vendor_purchase_order_items');
        Schema::dropIfExists('vendor_purchase_orders');
    }
};
