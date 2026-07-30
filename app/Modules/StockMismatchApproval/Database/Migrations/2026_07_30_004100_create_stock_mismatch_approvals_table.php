<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 83 — Stock Mismatch Approval.
 *
 * Request / response approval workflow raised against a stock count when a
 * physical-vs-system variance needs management sign-off. Captures the variance
 * reason, the requester and approver, the management response and recount
 * outcome, the adjustment method and communication mode, and the → requested /
 * under_review / approved / rejected / cancelled lifecycle with stamped
 * timestamps. Header + note attachments. Doc series: `SMA-#####`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mismatch_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->nullable()->unique();

            $table->foreignId('stock_count_id')->nullable()->constrained('stock_counts')->nullOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('requested_to_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('approval_status', 20)->default('requested'); // requested / under_review / approved / rejected / cancelled

            $table->string('variance_reason', 30)->nullable();
            $table->string('management_response', 20)->nullable(); // adjust / on_hold / recount / reinvestigate / write_off
            $table->string('recount_outcome', 25)->nullable();
            $table->string('adjustment_method', 20)->nullable(); // foc_purchase / issue_consumption
            $table->string('communication_mode', 15)->nullable(); // whatsapp / email / phone_call

            $table->text('notes')->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('approval_status');
            $table->index('created_at');
        });

        Schema::create('stock_mismatch_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_mismatch_approval_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 25)->nullable(); // mismatch_note / investigation_note / approval_note
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['stock_mismatch_approval_id', 'sequence_no'], 'sma_attachments_approval_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mismatch_approval_attachments');
        Schema::dropIfExists('stock_mismatch_approvals');
    }
};
