<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 43 — Outside Labour Return Request / Response (Warranty Claim).
 *
 * A SINGLE module covering both Parts and Labour returns / warranty claims: the
 * service advisor asks an outside vendor / contractor to resolve a job under
 * warranty (rework / replacement) or settle the amount, and the vendor accepts,
 * rejects or counter-proposes. Header (claim) + item-wise return lines (spare or
 * labour, each carrying before/after photo evidence + OL-bill ref) + document /
 * evidence attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_return_notes', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->nullable()->unique();

            $table->string('return_type', 20)->nullable(); // regular / warranty
            $table->string('claim_type', 30)->nullable();   // outside_labour_warranty / parts_warranty

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete(); // outside labour vendor / contractor
            $table->foreignId('transport_company_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('regular_sales_invoice_id')->nullable()->constrained('regular_sales_invoices')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('return_reason', 40)->nullable();
            $table->string('warranty_type', 20)->nullable();  // within_warranty / warranty_expired
            $table->string('warranty_period', 20)->nullable(); // 1_month / 3_months / 6_months / 12_months
            $table->string('rework_type', 20)->nullable();     // refit / repair_again / repaint
            $table->string('tat_option', 20)->nullable();      // one_day / two_days / three_days / custom
            $table->unsignedSmallInteger('tat_custom_days')->nullable();

            $table->string('status', 30)->default('requested');
            $table->string('counter_proposal', 30)->nullable(); // rework_free / shared_cost
            $table->string('rejection_reason', 40)->nullable();
            $table->string('vendor_rating_type', 20)->nullable();

            $table->decimal('recovery_amount', 12, 2)->nullable();

            $table->string('reminder_frequency', 20)->nullable();
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
            $table->index('return_type');
        });

        Schema::create('goods_return_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_return_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outside_labour_bill_id')->nullable()->constrained('outside_labour_bills')->nullOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            $table->string('item_type', 10)->default('spare'); // spare / labour
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->string('material_condition', 20)->nullable(); // new / used / unused / open_box

            // Item-wise photo evidence.
            $table->string('before_photo_path')->nullable();
            $table->string('after_photo_path')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_return_note_id', 'sequence_no'], 'grtn_items_note_sequence_index');
        });

        Schema::create('goods_return_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_return_note_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // vendor_bill_copy / warranty_card / complaint_photo / damage_photo / work_order_copy / inspection_report / front_view / rear_view / left_side / right_side / fault_evidence
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_return_note_id', 'sequence_no'], 'grtn_attachments_note_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_return_note_attachments');
        Schema::dropIfExists('goods_return_note_items');
        Schema::dropIfExists('goods_return_notes');
    }
};
