<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 35 — VPO (Vendor Purchase Order) Approval / Response.
 *
 * Admin approval for a vendor purchase order (stock bulk / odd item / high
 * value / rate contract / emergency), line-by-line, before the PO is issued.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpo_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->string('po_approval_type', 25)->nullable(); // stock_bulk / odd_item / high_value / rate_contract / job_card / emergency
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // store in charge
            $table->foreignId('vendor_purchase_inquiry_id')->nullable()->constrained('vendor_purchase_inquiries')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('part_type_id')->nullable()->constrained('part_types')->nullOnDelete(); // inventory type
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('approval_mode_id')->nullable()->constrained('customer_approval_types')->nullOnDelete();

            $table->string('approval_level', 20)->nullable();       // l1_store / l2_accounts / l3_owner
            $table->string('payment_term', 20)->nullable();         // advance / cod / credit_7 / credit_15 / credit_30
            $table->string('vendor_category', 20)->nullable();      // preferred / approved / backup
            $table->string('status', 25)->default('sent');          // sent / partially_approved / fully_approved / under_review / rejected / reapproved
            $table->string('rejection_reason', 30)->nullable();

            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('vpo_approval_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpo_approval_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);

            // Per-line approval.
            $table->boolean('part_approved')->default(true);
            $table->decimal('qty_approved', 12, 2)->nullable();
            $table->decimal('rate_approved', 12, 2)->nullable();
            $table->decimal('discount_approved', 12, 2)->nullable();
            $table->string('tat_approved', 20)->nullable();
            // Reference figures for the approver.
            $table->decimal('last_purchase_price', 12, 2)->nullable();
            $table->string('last_purchase_vendor')->nullable();
            $table->date('last_purchase_date')->nullable();

            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vpo_approval_id', 'sequence_no'], 'vpa_items_seq_index');
        });

        Schema::create('vpo_approval_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpo_approval_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vpo_approval_id', 'sequence_no'], 'vpa_charges_seq_index');
        });

        Schema::create('vpo_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpo_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 25)->nullable(); // vendor_quotation / quote_comparison / approval_notes
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vpo_approval_id', 'sequence_no'], 'vpa_attachments_seq_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpo_approval_attachments');
        Schema::dropIfExists('vpo_approval_charges');
        Schema::dropIfExists('vpo_approval_items');
        Schema::dropIfExists('vpo_approvals');
    }
};
