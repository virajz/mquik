<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 25 — Customer Advance Receipt Request.
 *
 * Request an advance payment from the customer (typically via a link): the
 * purpose, how the amount is derived (% of estimate / min / max / custom),
 * payment status, reminder scheduling and follow-up. Nothing is actually sent
 * — reminders are configuration only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_receipt_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('sales_estimate_id')->nullable()->constrained('sales_estimates')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('advance_purpose', 30)->nullable();  // odd_item_po / urgent_po / regular_parts / outside_labour
            $table->string('amount_type', 20)->nullable();      // percent_of_estimate / minimum / maximum / custom
            $table->decimal('percent', 5, 2)->nullable();       // when amount_type = percent_of_estimate
            $table->decimal('amount', 12, 2)->nullable();       // the requested advance amount
            $table->string('payment_status', 20)->default('requested'); // requested / partially_paid / fully_paid / cancelled / failed / rejected / refunded
            $table->string('reminder_time', 20)->nullable();    // daily_10am / afternoon_2pm / evening_4pm / custom
            $table->string('reminder_custom_time', 20)->nullable();
            $table->string('rejection_reason', 30)->nullable(); // customer_not_interested / budget_issue / delay_estimate / insurance_limitation

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('job_card_id');
        });

        Schema::create('advance_receipt_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_receipt_request_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10)->default('image'); // image / pdf
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['advance_receipt_request_id', 'sequence_no'], 'arr_attachments_request_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_receipt_request_attachments');
        Schema::dropIfExists('advance_receipt_requests');
    }
};
