<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Row 5 (Pickup/Drop) — bring the job record up to the CSV scope: the booked
 * option, time slot, separate pickup and drop addresses, distance charging,
 * routing (advisor/department/service type), the seven-state lifecycle,
 * reasons, OTP handover verification, and the checklist/complaint/photo lines.
 *
 * All of the lookups are existing masters — only DistanceSlabMaster is new.
 */
return new class extends Migration
{
    /** Old status value => new seven-state lifecycle value. */
    private const STATUS_MAP = [
        'scheduled' => 'pending',
        'picked' => 'vehicle_collected',
        'in_transit' => 'driver_on_the_way',
        'delivered' => 'vehicle_delivered',
        'cancelled' => 'cancelled',
    ];

    public function up(): void
    {
        Schema::table('pickup_drops', function (Blueprint $table) {
            // What the customer booked, and when.
            $table->foreignId('pickup_drop_option_id')->nullable()->after('direction')
                ->constrained('pickup_drop_options')->nullOnDelete();
            $table->foreignId('time_slot_id')->nullable()->after('scheduled_at')
                ->constrained('time_slots')->nullOnDelete();

            // Routing.
            $table->foreignId('advisor_employee_id')->nullable()->after('driver_employee_id')
                ->constrained('employees')->nullOnDelete();
            $table->foreignId('workshop_department_id')->nullable()->after('advisor_employee_id')
                ->constrained('workshop_departments')->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->after('workshop_department_id')
                ->constrained('service_types')->nullOnDelete();

            // Charging by distance band.
            $table->foreignId('distance_slab_id')->nullable()->after('service_type_id')
                ->constrained('distance_slabs')->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable()->after('distance_slab_id');
            $table->decimal('distance_charge', 10, 2)->nullable()->after('distance_km');

            // A "pickup & drop both" job carries both legs' addresses.
            $table->foreignId('pickup_region_id')->nullable()->after('address')
                ->constrained('regions')->nullOnDelete();
            $table->text('drop_address')->nullable()->after('pickup_region_id');
            $table->foreignId('drop_region_id')->nullable()->after('drop_address')
                ->constrained('regions')->nullOnDelete();

            // Reasons — shared masters, also used by Appointment.
            $table->foreignId('pending_reason_id')->nullable()->after('status')
                ->constrained('pending_reasons')->nullOnDelete();
            $table->foreignId('reschedule_reason_id')->nullable()->after('pending_reason_id')
                ->constrained('pending_reasons')->nullOnDelete();
            $table->foreignId('cancel_reason_id')->nullable()->after('reschedule_reason_id')
                ->constrained('cancel_reasons')->nullOnDelete();
            $table->dateTime('rescheduled_from_at')->nullable()->after('cancel_reason_id');

            // Handover verification.
            $table->string('otp_mode', 10)->default('optional')->after('rescheduled_from_at'); // optional | mandatory
            $table->string('pickup_otp', 10)->nullable()->after('otp_mode');
            $table->dateTime('pickup_otp_verified_at')->nullable()->after('pickup_otp');
            $table->string('delivery_otp', 10)->nullable()->after('pickup_otp_verified_at');
            $table->dateTime('delivery_otp_verified_at')->nullable()->after('delivery_otp');

            // Document checklist the driver must collect with the vehicle.
            $table->foreignId('checklist_template_id')->nullable()->after('delivery_otp_verified_at')
                ->constrained('checklist_templates')->nullOnDelete();

            $table->index(['time_slot_id', 'scheduled_at']);
        });

        // `address` now means specifically the pickup address.
        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->renameColumn('address', 'pickup_address');
        });

        foreach (self::STATUS_MAP as $old => $new) {
            DB::table('pickup_drops')->where('status', $old)->update(['status' => $new]);
        }

        Schema::create('pickup_drop_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_drop_id')->constrained('pickup_drops')->cascadeOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->nullOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            $table->text('description');
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['pickup_drop_id', 'sequence_no']);
        });

        // Snapshotted from the chosen checklist template so later template edits
        // never rewrite what a driver actually collected.
        Schema::create('pickup_drop_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_drop_id')->constrained('pickup_drops')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_collected')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['pickup_drop_id', 'sequence_no']);
        });

        // Condition evidence, captured at pickup and again at drop.
        Schema::create('pickup_drop_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_drop_id')->constrained('pickup_drops')->cascadeOnDelete();
            $table->foreignId('photo_type_id')->nullable()->constrained('photo_types')->nullOnDelete();
            $table->string('leg', 10)->default('pickup'); // pickup | drop
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['pickup_drop_id', 'leg', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_drop_photos');
        Schema::dropIfExists('pickup_drop_documents');
        Schema::dropIfExists('pickup_drop_complaints');

        foreach (array_flip(self::STATUS_MAP) as $new => $old) {
            DB::table('pickup_drops')->where('status', $new)->update(['status' => $old]);
        }

        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->renameColumn('pickup_address', 'address');
        });

        Schema::table('pickup_drops', function (Blueprint $table) {
            $table->dropIndex(['time_slot_id', 'scheduled_at']);

            foreach ([
                'pickup_drop_option_id', 'time_slot_id', 'advisor_employee_id', 'workshop_department_id',
                'service_type_id', 'distance_slab_id', 'pickup_region_id', 'drop_region_id',
                'pending_reason_id', 'reschedule_reason_id', 'cancel_reason_id', 'checklist_template_id',
            ] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }

            $table->dropColumn([
                'distance_km', 'distance_charge', 'drop_address', 'rescheduled_from_at',
                'otp_mode', 'pickup_otp', 'pickup_otp_verified_at', 'delivery_otp', 'delivery_otp_verified_at',
            ]);
        });
    }
};
