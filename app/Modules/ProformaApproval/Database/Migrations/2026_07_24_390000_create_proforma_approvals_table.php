<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 55 — Proforma Approval Request / Response.
 *
 * The staged digital approval of a proforma before it is converted to an
 * invoice: Billing Executive prepares → Store In-charge / Service Advisor
 * approve → Admin approves → convert to invoice. Header (the proforma being
 * approved, with a timestamp per stage) + per-role checkpoint lines (the
 * verification points each approver ticks / flags) + document attachments.
 * Reminder / link automation is config-only (never sent), per policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->string('approval_stage', 20)->nullable();     // stage_1 … stage_6
            $table->string('approval_authority', 30)->nullable(); // billing_executive / store_incharge / service_advisor / workshop_admin / customer_insurance
            $table->string('priority', 10)->default('normal');    // normal / high / urgent
            $table->string('status', 30)->default('under_preparation');

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete(); // service contractor / outside labour vendor
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->foreignId('billing_executive_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('store_incharge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('loss_reason', 30)->nullable();
            $table->string('missing_reason', 30)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->string('rejection_reason', 40)->nullable();
            $table->string('proforma_reference')->nullable(); // print-preview reference

            $table->decimal('amount', 12, 2)->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('prepared_at')->nullable();
            $table->dateTime('store_approved_at')->nullable();
            $table->dateTime('advisor_approved_at')->nullable();
            $table->dateTime('admin_approved_at')->nullable();
            $table->dateTime('converted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('approval_stage');
            $table->index('job_card_id');
        });

        Schema::create('proforma_approval_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_approval_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);           // billing_executive / store_incharge / service_advisor / admin
            $table->string('checkpoint', 40);     // key from the role's checkpoint list
            $table->string('status', 10)->default('ok'); // ok / flagged
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['proforma_approval_id', 'sequence_no'], 'pfa_checkpoints_approval_sequence_index');
        });

        Schema::create('proforma_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 30)->nullable(); // proforma_format_1 / proforma_format_2 / approval_screenshot
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['proforma_approval_id', 'sequence_no'], 'pfa_attachments_approval_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_approval_attachments');
        Schema::dropIfExists('proforma_approval_checkpoints');
        Schema::dropIfExists('proforma_approvals');
    }
};
