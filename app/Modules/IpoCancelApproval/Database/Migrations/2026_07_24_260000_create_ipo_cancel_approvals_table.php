<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 33 — IPO Cancel Request / Response.
 *
 * Advisor requests to cancel specific un-issued parts on an Internal Part
 * Order: reason, downstream impact, issue/return status, and an approval
 * hierarchy with rejection handling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipo_cancel_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('cancel_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('internal_part_order_id')->nullable()->constrained('internal_part_orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('spare_id')->nullable()->constrained('spares')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();

            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('cancellation_reason', 30)->nullable();
            $table->json('impacts')->nullable();
            $table->string('cancellation_category', 20)->nullable(); // operational_error / customer_driven
            $table->string('issue_status', 20)->nullable();          // pending / issued / partially_issued / returned / backorder
            $table->string('return_status', 20)->nullable();         // pending / returned / rejected
            $table->string('return_type', 20)->nullable();
            $table->string('rejection_reason', 30)->nullable();
            $table->string('status', 25)->default('under_review');   // under_review / approved / rejected / cancelled / reversed / needs_clarification
            $table->string('approval_level', 20)->nullable();        // advisor / store_manager / workshop_manager

            $table->dateTime('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('internal_part_order_id');
        });

        Schema::create('ipo_cancel_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipo_cancel_approval_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['ipo_cancel_approval_id', 'sequence_no'], 'ica_attachments_seq_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipo_cancel_approval_attachments');
        Schema::dropIfExists('ipo_cancel_approvals');
    }
};
