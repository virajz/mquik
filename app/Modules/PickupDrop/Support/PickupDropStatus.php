<?php

namespace App\Modules\PickupDrop\Support;

use App\Modules\Appointment\Support\AppointmentStatus;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\PickupDrop\Models\PickupDrop;

/**
 * Works out a pickup/drop job's status from what the driver has actually done.
 *
 * Same doctrine as AppointmentStatus: nobody types this. Each rung is read off a
 * recorded fact — assignment, the departed/reached stamps, the OTP or condition
 * photos taken where the car changed hands, and (for a pickup) the vehicle's
 * inward at the gate. Cancelling stays a deliberate act (`cancelled_at`).
 *
 * The two legs top out differently: a pickup ends when the car is inside the
 * workshop (inward recorded); a drop ends when the customer has the car back
 * (delivery confirmed) — there is no later gate event to observe for it.
 */
class PickupDropStatus
{
    public static function derive(PickupDrop $job): string
    {
        if ($job->cancelled_at !== null) {
            return PickupDrop::STATUS_CANCELLED;
        }

        if ($job->direction === PickupDrop::DIRECTION_PICKUP) {
            if (self::hasInwardAfterCreation($job)) {
                return PickupDrop::STATUS_COMPLETED;
            }

            if (self::collectionConfirmed($job)) {
                return PickupDrop::STATUS_VEHICLE_COLLECTED;
            }
        } else {
            if (self::deliveryConfirmed($job)) {
                return PickupDrop::STATUS_VEHICLE_DELIVERED;
            }
        }

        if ($job->departed_at !== null) {
            return PickupDrop::STATUS_DRIVER_ON_THE_WAY;
        }

        if ($job->driver_employee_id !== null || $job->vendor_courier_id !== null) {
            return PickupDrop::STATUS_DRIVER_ASSIGNED;
        }

        return PickupDrop::STATUS_PENDING;
    }

    /** Recompute and persist without re-firing the events that call this. */
    public static function refresh(?PickupDrop $job): void
    {
        if (! $job) {
            return;
        }

        $derived = self::derive($job);

        if ($derived !== $job->status) {
            $job->forceFill(['status' => $derived])->saveQuietly();
        }

        // The appointment overlays this job's stage; a quiet save skips the model
        // events it listens to, so it is nudged by hand.
        if ($job->appointment_id) {
            AppointmentStatus::refresh($job->appointment()->first());
        }
    }

    /** Recompute every open job for a vehicle — the gate calls this on inward. */
    public static function refreshForVehicle(?int $customerVehicleId): void
    {
        if (! $customerVehicleId) {
            return;
        }

        PickupDrop::query()
            ->where('customer_vehicle_id', $customerVehicleId)
            ->whereNull('cancelled_at')
            ->whereNotIn('status', [PickupDrop::STATUS_COMPLETED])
            ->get()
            ->each(fn (PickupDrop $job) => self::refresh($job));
    }

    /**
     * The handover happened: an explicit confirmation stamp, a verified pickup
     * OTP, or condition photos taken at the pickup address.
     */
    protected static function collectionConfirmed(PickupDrop $job): bool
    {
        return $job->collected_at !== null
            || $job->pickup_otp_verified_at !== null
            || $job->photos()->where('leg', PickupDrop::LEG_PICKUP)->whereNotNull('path')->exists();
    }

    protected static function deliveryConfirmed(PickupDrop $job): bool
    {
        return $job->delivered_at !== null
            || $job->delivery_otp_verified_at !== null
            || $job->photos()->where('leg', PickupDrop::LEG_DROP)->whereNotNull('path')->exists();
    }

    /** The car reached the workshop: an inward for the vehicle after this job existed. */
    protected static function hasInwardAfterCreation(PickupDrop $job): bool
    {
        if (! $job->customer_vehicle_id) {
            return false;
        }

        return GateInOut::query()
            ->where('customer_vehicle_id', $job->customer_vehicle_id)
            ->where('entered_at', '>=', $job->created_at)
            ->exists();
    }
}
