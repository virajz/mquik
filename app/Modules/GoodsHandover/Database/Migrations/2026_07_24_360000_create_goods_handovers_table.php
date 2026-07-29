<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 46 — Goods Handover / Technician Parts Return.
 *
 * The store hands received parts to a technician (against a GRN / FWO / job
 * card) and captures any parts the technician returns (excess, wrong, not
 * required, defective). Header + item-wise lines (each with material condition,
 * physical-verification outcome, returned qty and a spare photo) + footer
 * evidence attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_no')->nullable()->unique();

            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('handover_by_id')->nullable()->constrained('employees')->nullOnDelete();   // store executive
            $table->foreignId('received_by_id')->nullable()->constrained('employees')->nullOnDelete();   // technician
            $table->foreignId('verified_by_id')->nullable()->constrained('employees')->nullOnDelete();   // floor in-charge / advisor
            $table->foreignId('parts_return_by_id')->nullable()->constrained('employees')->nullOnDelete(); // technician / advisor
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();          // service contractor
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('final_work_order_id')->nullable()->constrained('final_work_orders')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();

            $table->string('material_return_status', 20)->nullable(); // partial_return / full_return
            $table->string('return_reason', 30)->nullable();          // excess_issue / wrong_part_issued / part_not_required / defective_part
            $table->string('status', 20)->default('received');        // received / verification_pending / verified / mismatch_accepted / mismatch_rejected

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('goods_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('spare_brand_id')->nullable()->constrained('spare_brands')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);          // issued qty
            $table->decimal('returned_quantity', 12, 2)->nullable();
            $table->string('material_condition', 20)->nullable();     // new / used / refurbished / repairable / repaired / scrap
            $table->string('physical_verification', 20)->nullable();  // excess_qty / less_qty / physical_damage / wrong_part / manufacturing_defect / missing_item / expired_material / packaging_damage
            $table->string('damage_type', 20)->nullable();            // old_damage / fitment_damage
            $table->string('photo_path')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_handover_id', 'sequence_no'], 'gho_items_handover_sequence_index');
        });

        Schema::create('goods_handover_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_handover_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // damage_photo / fault_evidence
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['goods_handover_id', 'sequence_no'], 'gho_attachments_handover_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_handover_attachments');
        Schema::dropIfExists('goods_handover_items');
        Schema::dropIfExists('goods_handovers');
    }
};
