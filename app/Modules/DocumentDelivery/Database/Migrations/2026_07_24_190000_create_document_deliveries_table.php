<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 22 — Document Delivery.
 *
 * Track outbound document movement to the customer / insurer (the opposite
 * direction of DocumentCollection): who delivered what, by which mode, the
 * acknowledgement, and delivery status — with a per-document checklist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no')->nullable()->unique();

            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->nullOnDelete();

            // Insurer / delivery address (free text — state/city/area).
            $table->string('delivery_state')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_area')->nullable();

            $table->foreignId('advisor_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('driver_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('courier_company_id')->nullable()->constrained('courier_companies')->nullOnDelete();
            $table->foreignId('missing_document_reason_id')->nullable()->constrained('missing_document_reasons')->nullOnDelete();
            $table->foreignId('follow_up_mode_id')->nullable()->constrained('follow_up_modes')->nullOnDelete();

            $table->string('recipient_type', 20)->nullable();      // owner_self / on_behalf
            $table->string('delivery_mode', 20)->nullable();       // hand_to_hand / porter / courier
            $table->string('acknowledgement_type', 20)->nullable(); // physical_sign / digital_sign / otp / email
            $table->string('status', 20)->default('pending');      // pending / delivered / in_transit / returned / re_sent
            $table->string('delivery_failure_reason', 30)->nullable(); // wrong_address / door_locked / recipient_unavailable

            $table->string('reminder_frequency', 20)->nullable();  // daily / every_2_days / custom
            $table->unsignedSmallInteger('reminder_custom_days')->nullable();

            $table->dateTime('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('job_card_id');
            $table->index(['insurance_company_id', 'status']);
        });

        Schema::create('document_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_delivery_id')->constrained()->cascadeOnDelete();
            $table->string('document_name'); // RC Book / Insurance Policy / DL / Aadhar / PAN / PUC / …
            $table->boolean('is_delivered')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['document_delivery_id', 'sequence_no'], 'dd_items_delivery_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_delivery_items');
        Schema::dropIfExists('document_deliveries');
    }
};
