<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 56 — Delivery Order (DO) Request / Response.
 *
 * After a proforma is approved, the workshop sends it to the insurance company,
 * which verifies items / rates / total and issues a Delivery Order (DO) — the
 * confirmation to deliver the vehicle. This records the DO against the claim,
 * captures request / received / entry / approved timestamps and the DO amount,
 * and flags any mismatch vs the proforma amount. Header + document attachments.
 * Reminders are config-only (never sent), per policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('proforma_approval_id')->nullable()->constrained('proforma_approvals')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('surveyor_name')->nullable();

            // Insurance claim reference.
            $table->string('claim_number', 80)->nullable();
            $table->date('claim_date')->nullable();
            $table->string('policy_number', 80)->nullable();

            $table->string('status', 30)->default('requested'); // requested / under_verification / on_hold / do_received / requested_to_settle / mismatch_approved / cancelled
            $table->string('mismatch_reason', 30)->nullable();   // labour_reduction / parts_reduction / paint_reduction / depreciation / non_approved_item / policy_limitation

            $table->decimal('proforma_amount', 12, 2)->nullable();
            $table->decimal('do_amount', 12, 2)->nullable();
            $table->text('do_description')->nullable();

            $table->string('reminder_frequency', 20)->nullable();
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('do_received_at')->nullable();
            $table->dateTime('do_entry_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('job_card_id');
            $table->index('created_at');
        });

        Schema::create('delivery_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // proforma_copy / do_copy / surveyor_consent / customer_consent
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['delivery_order_id', 'sequence_no'], 'do_attachments_order_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_attachments');
        Schema::dropIfExists('delivery_orders');
    }
};
