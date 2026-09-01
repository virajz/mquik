<?php

namespace App\Modules\Appointment\Support;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PickupDrop\Models\PickupDrop;

/**
 * Works out an appointment's status from what has actually happened to the car.
 *
 * Nobody sets this by hand. A status somebody types drifts the moment they
 * forget to come back and change it, and a booking that still reads "Confirmed"
 * while the vehicle is on a lift is worse than no status at all. So the ladder
 * below is read off real events, each of which is recorded somewhere else for
 * its own reasons:
 *
 *   cancelled  — somebody called it off (`cancelled_at`)
 *   completed  — a job card was raised against the booking; the booking's job
 *                is done at that point. The return leg carries on inside the
 *                PickupDrop job, which has its own lifecycle.
 *   arrived    — the car reached the workshop and a gate visit (inward) exists
 *   collected  — the driver picked the car up and photographed it
 *   no_show    — the slot passed with none of the above
 *   pending    — a pending reason is on file: that IS what pending means now
 *   confirmed  — booked, nothing has happened yet
 *
 * Ordering is precedence, highest first: a completed booking stays completed
 * even though its earlier stages also still hold true.
 */
class AppointmentStatus
{
    public static function derive(Appointment $appointment): string
    {
        if ($appointment->cancelled_at !== null) {
            return Appointment::STATUS_CANCELLED;
        }

        if (self::hasJobCard($appointment)) {
            return Appointment::STATUS_COMPLETED;
        }

        if (self::hasArrived($appointment)) {
            return Appointment::STATUS_ARRIVED;
        }

        if (self::wasCollected($appointment)) {
            return Appointment::STATUS_VEHICLE_COLLECTED;
        }

        if (self::isNoShow($appointment)) {
            return Appointment::STATUS_NO_SHOW;
        }

        if ($appointment->pending_reason_id !== null) {
            return Appointment::STATUS_PENDING;
        }

        if ($appointment->rescheduled_from_at !== null) {
            return Appointment::STATUS_RESCHEDULED;
        }

        return Appointment::STATUS_CONFIRMED;
    }

    /**
     * Recompute and persist, without touching timestamps or firing the model
     * events that would recurse straight back into here.
     */
    public static function refresh(?Appointment $appointment): void
    {
        if (! $appointment) {
            return;
        }

        $derived = self::derive($appointment);

        if ($derived === $appointment->status) {
            return;
        }

        $appointment->forceFill(['status' => $derived])->saveQuietly();
    }

    /** Recompute every open booking for a vehicle — used by gate and job-card events. */
    public static function refreshForVehicle(?int $customerVehicleId): void
    {
        if (! $customerVehicleId) {
            return;
        }

        Appointment::query()
            ->where('customer_vehicle_id', $customerVehicleId)
            ->whereNull('cancelled_at')
            ->where('status', '!=', Appointment::STATUS_COMPLETED)
            ->get()
            ->each(fn (Appointment $a) => self::refresh($a));
    }

    protected static function hasJobCard(Appointment $appointment): bool
    {
        return JobCard::query()->where('appointment_id', $appointment->id)->exists();
    }

    /**
     * An inward raised against this booking.
     *
     * This used to be inferred — same vehicle, entered any time after the
     * booking was created — which with no upper bound matched visits weeks
     * apart: a booking for 03/08 read "Arrived" off a gate entry on 19/08.
     * `gate_visits.appointment_id` makes it a recorded fact, so a car that
     * happens to return months later no longer back-dates an old booking.
     */
    protected static function hasArrived(Appointment $appointment): bool
    {
        return GateInOut::query()
            ->where('appointment_id', $appointment->id)
            ->exists();
    }

    /** The driver has the car — collected, delivered or done are all past this point. */
    protected static function wasCollected(Appointment $appointment): bool
    {
        return $appointment->pickupDrops()
            ->whereIn('status', [
                PickupDrop::STATUS_VEHICLE_COLLECTED,
                PickupDrop::STATUS_VEHICLE_DELIVERED,
                PickupDrop::STATUS_COMPLETED,
            ])
            ->exists();
    }

    /** The slot came and went and the car never turned up. */
    protected static function isNoShow(Appointment $appointment): bool
    {
        return $appointment->appointment_at !== null
            && $appointment->appointment_at->isPast()
            && $appointment->pending_reason_id === null;
    }
}
