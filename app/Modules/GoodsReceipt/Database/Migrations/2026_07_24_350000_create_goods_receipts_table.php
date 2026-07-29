<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 45 — Goods Receive & Verification (GRN).
 *
 * The store receives vendor parts (against a PO or direct), physically verifies
 * each line against the order, does QC and per-line approval (part / qty / rate
 * / discount / TAT), and allocates storage. Header + item-wise lines (each with
 * material condition, physical-verification outcome, damage type, storage bin,
 * per-line approval and a spare photo) + footer evidence attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no')->nullable()->unique();

            $table->string('goods_receipt_type', 20)->default('against_po'); // against_po / direct_receipt
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('vendor_purchase_order_id')->nullable()->constrained('vendor_purchase_orders')->nullOnDelete();
            $table->foreignId('vendor_purchase_inquiry_id')->nullable()->constrained('vendor_purchase_inquiries')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('employees')->nullOnDelete(); // store received by
            $table->foreignId('verified_by_id')->nullable()->constrained('employees')->nullOnDelete(); // store verified by
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('delivery_performance', 20)->nullable(); // on_time / delayed
            $table->string('approval_authority', 20)->nullable();   // store_executive / parts_manager / store_manager
            $table->string('vendor_category', 20)->nullable();
            $table->string('vendor_rating_type', 20)->nullable();
            $table->string('status', 20)->default('verification_pending'); // verification_pending / verified / mismatch_accepted / mismatch_rejected

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('floor_received_by_id')->nullable()->constrained('employees')->nullOnDelete();  // technician
            $table->foreignId('floor_verified_by_id')->nullable()->constrained('employees')->nullOnDelete();  // service advisor

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->string('material_condition', 20)->nullable();     // new / used / refurbished / repairable / repaired / scrap
            $table->string('physical_verification', 20)->nullable();  // ok / excess_qty / less_qty / physical_damage / wrong_part / manufacturing_defect / missing_item / expired_material / packaging_damage
            $table->string('damage_type', 20)->nullable();            // transit / manufacturing / unloading / storage / fitment
            $table->string('storage_allocation', 20)->nullable();     // A1, A2, B3…

            // Per-line approval (Parts / Store Manager).
            $table->boolean('part_approved')->default(false);
            $table->decimal('quantity_approved', 12, 2)->nullable();
            $table->decimal('rate_approved', 12, 2)->nullable();
            $table->decimal('discount_approved', 12, 2)->nullable();
            $table->string('tat_approved', 40)->nullable();
            $table->decimal('last_purchase_price', 12, 2)->nullable();
            $table->string('last_purchase_vendor')->nullable();
            $table->date('last_purchase_date')->nullable();

            $table->string('photo_path')->nullable(); // spare photo / image capture

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_receipt_id', 'sequence_no'], 'grn_items_receipt_sequence_index');
        });

        Schema::create('goods_receipt_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // damage_photo / fault_evidence / invoice_copy
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_receipt_id', 'sequence_no'], 'grn_attachments_receipt_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_attachments');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
