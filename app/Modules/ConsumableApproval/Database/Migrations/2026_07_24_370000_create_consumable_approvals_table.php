<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 50 — Consumable Approval Request / Response.
 *
 * The floor requests approval to book a consumable loss / damage (paint, VA,
 * workshop consumables, or a labour loss) against a job card; the store
 * manager / owner approves, holds or rejects. Header (request) + item-wise
 * loss lines (each with the loss/damage type, purchase / outside-labour ref and
 * a damaged photo) + an approval-screenshot attachment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('approval_authority_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('consumable_category', 20)->nullable(); // paint / va / workshop / other
            $table->string('priority', 10)->default('normal');      // normal / medium / high
            $table->string('status', 20)->default('requested');     // requested / on_hold / under_review / approved / rejected
            $table->string('approval_response', 30)->nullable();    // justification_required / other

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('consumable_category');
            $table->index('created_at');
        });

        Schema::create('consumable_approval_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_approval_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_purchase_order_id')->nullable()->constrained('vendor_purchase_orders')->nullOnDelete();   // purchase reference
            $table->foreignId('outside_labour_order_id')->nullable()->constrained('outside_labour_orders')->nullOnDelete();     // outside labour reference
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('hsn_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();

            $table->string('item_type', 10)->default('spare'); // spare / labour
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->string('loss_damage_type', 30)->nullable();
            $table->string('damaged_photo_path')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['consumable_approval_id', 'sequence_no'], 'ca_items_request_sequence_index');
            $table->index('loss_damage_type');
        });

        Schema::create('consumable_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // approval_screenshot
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['consumable_approval_id', 'sequence_no'], 'ca_attachments_request_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_approval_attachments');
        Schema::dropIfExists('consumable_approval_items');
        Schema::dropIfExists('consumable_approvals');
    }
};
