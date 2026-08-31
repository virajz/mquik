<?php

namespace App\Modules\VehicleInspectionOrder\Support;

use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope;
use Illuminate\Support\Collection;

/**
 * Works out a work order's status from the technician's own timers.
 *
 * Same doctrine as AppointmentStatus and PickupDropStatus: nobody types this.
 * The advisor sets up the order and assigns it; from there every rung is read
 * off the bench — a line running, a line paused with a reason, every line
 * finished. Cancelling stays a deliberate act.
 */
class InspectionOrderStatus
{
    public static function derive(VehicleInspectionOrder $order): string
    {
        // Cancelling is the one deliberate act left; it sticks once set.
        if ($order->status === VehicleInspectionOrder::STATUS_CANCELLED) {
            return VehicleInspectionOrder::STATUS_CANCELLED;
        }

        /** @var Collection<int, VehicleInspectionOrderScope> $scopes */
        $scopes = $order->relationLoaded('workScopes') ? $order->workScopes : $order->workScopes()->get();

        if ($scopes->isNotEmpty()) {
            // Anything on the clock right now outranks the rest: the car is being worked on.
            if ($scopes->contains(fn ($s) => $s->work_status === VehicleInspectionOrderScope::STATUS_IN_PROGRESS)) {
                return VehicleInspectionOrder::STATUS_WIP;
            }

            if ($scopes->every(fn ($s) => $s->work_status === VehicleInspectionOrderScope::STATUS_COMPLETED)) {
                return VehicleInspectionOrder::STATUS_COMPLETED;
            }

            // Paused with nothing else running — the order is genuinely stalled.
            if ($scopes->contains(fn ($s) => $s->work_status === VehicleInspectionOrderScope::STATUS_PAUSED)) {
                return VehicleInspectionOrder::STATUS_ON_HOLD;
            }

            // Started once and set down: partly done still counts as in progress.
            if ($scopes->contains(fn ($s) => (int) $s->duration_seconds > 0
                || $s->work_status === VehicleInspectionOrderScope::STATUS_COMPLETED)) {
                return VehicleInspectionOrder::STATUS_WIP;
            }
        }

        if ($order->technician_id !== null) {
            return VehicleInspectionOrder::STATUS_ASSIGNED;
        }

        return VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING;
    }

    /** Recompute and persist, stamping the lifecycle times the status implies. */
    public static function refresh(?VehicleInspectionOrder $order): void
    {
        if (! $order) {
            return;
        }

        $order->load('workScopes');
        $derived = self::derive($order);

        $changes = [];

        if ($derived !== $order->status) {
            $changes['status'] = $derived;
        }

        if ($derived === VehicleInspectionOrder::STATUS_ASSIGNED && ! $order->assigned_at) {
            $changes['assigned_at'] = now();
        }

        if ($derived === VehicleInspectionOrder::STATUS_WIP && ! $order->started_at) {
            $changes['started_at'] = now();
        }

        if ($derived === VehicleInspectionOrder::STATUS_COMPLETED && ! $order->ended_at) {
            $changes['ended_at'] = now();
        }

        // Reopened work: the end stamp would otherwise lie about an order still running.
        if ($derived !== VehicleInspectionOrder::STATUS_COMPLETED && $order->ended_at) {
            $changes['ended_at'] = null;
        }

        if ($changes !== []) {
            $order->forceFill($changes)->save();
        }
    }

    /** One line of plain English for why the order sits where it does. */
    public static function explain(?VehicleInspectionOrder $order): string
    {
        if (! $order || ! $order->exists) {
            return 'Set once the order is saved and a technician is assigned.';
        }

        return match (self::derive($order)) {
            VehicleInspectionOrder::STATUS_CANCELLED => 'This order was cancelled.',
            VehicleInspectionOrder::STATUS_COMPLETED => 'Every work scope line has been completed on the bench.',
            VehicleInspectionOrder::STATUS_ON_HOLD => 'A line is paused — the technician recorded the reason.',
            VehicleInspectionOrder::STATUS_WIP => 'The technician has started work on at least one line.',
            VehicleInspectionOrder::STATUS_ASSIGNED => 'Assigned and waiting for the technician to start.',
            default => 'Assign a technician to release this order to the bench.',
        };
    }
}
