<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Row 4 (Appointment) — replace the hardcoded channel/pickup flags with the new
 * masters, and add the slot, priority and reason links the CSV asks for.
 *
 * The old `channel` string and `requires_pickup` boolean are backfilled onto the
 * new FKs where the master rows already exist, then dropped — they are superseded
 * by booking_channels / pickup_drop_options.
 */
return new class extends Migration
{
    /** Old channel value → new booking_channels.code */
    private const CHANNEL_MAP = [
        'app' => 'APP',
        'website' => 'WEB',
        'email' => 'EMAIL',
        'phone_call' => 'PHONE',
    ];

    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('time_slot_id')->nullable()->after('appointment_at')
                ->constrained('time_slots')->nullOnDelete();
            $table->foreignId('booking_channel_id')->nullable()->after('time_slot_id')
                ->constrained('booking_channels')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->after('service_type_id')
                ->constrained('priorities')->nullOnDelete();
            $table->foreignId('pickup_drop_option_id')->nullable()->after('assigned_technician_id')
                ->constrained('pickup_drop_options')->nullOnDelete();
            $table->foreignId('cancel_reason_id')->nullable()->after('status')
                ->constrained('appointment_cancel_reasons')->nullOnDelete();
            $table->foreignId('pending_reason_id')->nullable()->after('cancel_reason_id')
                ->constrained('appointment_pending_reasons')->nullOnDelete();
            // Set when an appointment is moved, so "Rescheduled" keeps the original slot.
            $table->dateTime('rescheduled_from_at')->nullable()->after('pending_reason_id');

            $table->index(['time_slot_id', 'appointment_at']);
            $table->index(['booking_channel_id', 'status']);
        });

        $this->backfill();

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['channel', 'requires_pickup']);
        });

        Schema::create('appointment_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->nullOnDelete();
            $table->foreignId('job_description_id')->nullable()->constrained('job_descriptions')->nullOnDelete();
            $table->text('description');
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->timestamps();

            $table->index(['appointment_id', 'sequence_no']);
        });
    }

    /**
     * Carry existing rows across. Masters are seeded after migrations on a fresh
     * install, so a missing lookup simply leaves the FK null rather than failing.
     */
    private function backfill(): void
    {
        foreach (self::CHANNEL_MAP as $old => $code) {
            $channelId = DB::table('booking_channels')->where('code', $code)->value('id');

            if ($channelId !== null) {
                DB::table('appointments')->where('channel', $old)->update(['booking_channel_id' => $channelId]);
            }
        }

        $selfDropId = DB::table('pickup_drop_options')->where('code', 'SELF')->value('id');
        $workshopPickupId = DB::table('pickup_drop_options')->where('code', 'WPU')->value('id');

        if ($selfDropId !== null) {
            DB::table('appointments')->where('requires_pickup', false)->update(['pickup_drop_option_id' => $selfDropId]);
        }

        if ($workshopPickupId !== null) {
            DB::table('appointments')->where('requires_pickup', true)->update(['pickup_drop_option_id' => $workshopPickupId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_complaints');

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('channel', 20)->default('phone_call');
            $table->boolean('requires_pickup')->default(false);

            $table->dropIndex(['time_slot_id', 'appointment_at']);
            $table->dropIndex(['booking_channel_id', 'status']);

            $table->dropConstrainedForeignId('time_slot_id');
            $table->dropConstrainedForeignId('booking_channel_id');
            $table->dropConstrainedForeignId('priority_id');
            $table->dropConstrainedForeignId('pickup_drop_option_id');
            $table->dropConstrainedForeignId('cancel_reason_id');
            $table->dropConstrainedForeignId('pending_reason_id');
            $table->dropColumn('rescheduled_from_at');
        });
    }
};
