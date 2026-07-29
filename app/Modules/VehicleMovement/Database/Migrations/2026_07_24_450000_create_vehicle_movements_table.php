<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 68 — Vehicle Inward / Outward (single module for both directions).
 *
 * The gate/security log of a vehicle entering (inward) or leaving (outward) the
 * premises — parking slot, gate, outward purpose, driver, the security guard who
 * cleared exit, entry/exit timestamps (TAT is derived), the number plate reading
 * and captured images. Header + image attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_no')->nullable()->unique();

            $table->string('movement_type', 10)->default('inward'); // inward / outward
            $table->foreignId('customer_vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignId('gate_pass_approval_id')->nullable()->constrained('gate_pass_approvals')->nullOnDelete();
            $table->foreignId('delivered_by_id')->nullable()->constrained('employees')->nullOnDelete();   // driver / advisor
            $table->foreignId('security_guard_id')->nullable()->constrained('employees')->nullOnDelete();  // exit by

            $table->string('parking_slot', 20)->nullable(); // slot_1 / slot_2 / slot_3 / outside_gate
            $table->string('gate', 10)->nullable();          // gate_1 / gate_2
            $table->string('outward_type', 20)->nullable();  // trial_run / outside_labour / final_delivery / fuel_filling / puc_inspection / rto_passing
            $table->string('driver_type', 20)->nullable();   // customer_self / customer_representative / workshop_staff / vendor_driver / towing_driver
            $table->string('job_status', 12)->default('pending'); // pending / completed / cancelled
            $table->string('number_plate', 20)->nullable();

            $table->dateTime('entry_at')->nullable();
            $table->dateTime('exit_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('movement_type');
            $table->index('job_status');
            $table->index('created_at');
        });

        Schema::create('vehicle_movement_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_movement_id')->constrained()->cascadeOnDelete();
            $table->string('attachment_type', 20)->nullable(); // entry_photo / exit_photo / number_plate / other
            $table->string('kind', 10)->default('image');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['vehicle_movement_id', 'sequence_no'], 'vm_attachments_movement_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_movement_attachments');
        Schema::dropIfExists('vehicle_movements');
    }
};
