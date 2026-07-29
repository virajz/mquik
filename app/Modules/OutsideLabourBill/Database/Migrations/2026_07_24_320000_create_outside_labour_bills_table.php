<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 41 — Outside Labour Bill Receive & Verification.
 *
 * The workshop receives an outside vendor / contractor's physical invoice for
 * outside work (denting, painting, etc.) and verifies it — rate / qty / job
 * match — before it can be paid. Header + item-wise bill lines (each with its
 * job card / vehicle) + invoice-photo attachments. Reminders are config-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_labour_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->nullable()->unique();

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('service_specialist_id')->nullable()->constrained('service_specialists')->nullOnDelete(); // work category
            $table->foreignId('outside_labour_order_id')->nullable()->constrained('outside_labour_orders')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('bill_document_type', 20)->nullable(); // tax_invoice / bill_of_supply / e_invoice / bill_book_memo
            $table->string('vendor_bill_no', 80)->nullable();     // the vendor's own invoice number
            $table->date('bill_date')->nullable();
            $table->decimal('bill_amount', 12, 2)->nullable();

            $table->string('work_completion_type', 20)->nullable(); // pending / partially_completed / fully_completed / deferred
            $table->string('status', 20)->default('requested');     // requested / under_verification / on_hold / partially_verified / fully_verified / rejected / cancelled
            $table->string('hold_reason', 40)->nullable();
            $table->string('rejection_reason', 40)->nullable();
            $table->string('vendor_rating_type', 20)->nullable();

            $table->string('reminder_frequency', 20)->nullable();
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('outside_labour_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('verified_amount', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_bill_id', 'sequence_no'], 'olb_items_bill_sequence_index');
        });

        Schema::create('outside_labour_bill_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outside_labour_bill_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // original_invoice / duplicate_invoice / whatsapp_screenshot
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['outside_labour_bill_id', 'sequence_no'], 'olb_attachments_bill_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_labour_bill_attachments');
        Schema::dropIfExists('outside_labour_bill_items');
        Schema::dropIfExists('outside_labour_bills');
    }
};
