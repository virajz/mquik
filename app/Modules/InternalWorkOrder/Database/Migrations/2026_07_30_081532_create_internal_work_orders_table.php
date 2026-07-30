<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 92 — IWO (Internal Work Order) Request & Response.
 *
 * A centralized register for internal complaints / requests / work orders /
 * suggestions raised across departments, with the management response,
 * root-cause / corrective-action and per-stage timestamps for TAT tracking.
 * Header + file attachments. Doc series `IWO-#####`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('iwo_no')->nullable()->unique();

            $table->string('iwo_type', 20)->nullable();      // raise_complaint / raise_request / work_order / suggestion
            $table->string('iwo_category', 30)->nullable();  // cctv / biometric / software / … / other
            $table->string('priority', 10)->default('normal'); // normal / medium / high
            $table->string('department', 20)->nullable();    // hr / account / it / crm / stores / security / housekeeping / management

            $table->foreignId('requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('requested_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('status', 15)->default('requested'); // requested / under_review / on_hold / in_progress / resolved / cancelled
            $table->string('iwo_response', 30)->nullable();     // work_order_accepted / more_info_required / … / temporary_resolution
            $table->string('follow_up_mode', 20)->nullable();   // whatsapp / email / sms / mobile_app / telephonic_call / physical_visit
            $table->string('escalation', 20)->nullable();       // escalated_to_hr / escalated_to_owner
            $table->string('root_cause', 20)->nullable();       // human_error / process_failure / … / other
            $table->string('corrective_action', 25)->nullable(); // repair / replacement / … / other

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->dateTime('due_at')->nullable();
            $table->dateTime('complaint_at')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('work_started_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });

        Schema::create('internal_work_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_work_order_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // image / video / pdf / screenshot
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['internal_work_order_id', 'sequence_no'], 'iwo_attachments_iwo_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_work_order_attachments');
        Schema::dropIfExists('internal_work_orders');
    }
};
