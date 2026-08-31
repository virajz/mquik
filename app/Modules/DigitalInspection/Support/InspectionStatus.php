<?php

namespace App\Modules\DigitalInspection\Support;

use App\Modules\DigitalInspection\Models\DigitalInspection;

/**
 * Works out an inspection's status from the checklist itself.
 *
 * Same doctrine as AppointmentStatus, PickupDropStatus and InspectionOrderStatus:
 * nobody types this. Every rung is read off the items — how many have been
 * answered — so the status cannot disagree with the sheet it describes.
 * Cancelling stays a deliberate act.
 */
class InspectionStatus
{
    public static function derive(DigitalInspection $inspection): string
    {
        if ($inspection->status === DigitalInspection::STATUS_CANCELLED) {
            return DigitalInspection::STATUS_CANCELLED;
        }

        $items = $inspection->relationLoaded('items') ? $inspection->items : $inspection->items()->get();

        if ($items->isEmpty()) {
            return DigitalInspection::STATUS_PENDING;
        }

        $answered = $items->reject(fn ($item) => in_array($item->outcome, ['pending', '', null], true))->count();

        if ($answered === 0) {
            return DigitalInspection::STATUS_PENDING;
        }

        return $answered === $items->count()
            ? DigitalInspection::STATUS_COMPLETED
            : DigitalInspection::STATUS_WIP;
    }

    /** Recompute and persist, stamping the times the status implies. */
    public static function refresh(?DigitalInspection $inspection): void
    {
        if (! $inspection) {
            return;
        }

        $inspection->load('items');
        $derived = self::derive($inspection);
        $changes = [];

        if ($derived !== $inspection->status) {
            $changes['status'] = $derived;
        }

        if ($derived === DigitalInspection::STATUS_WIP && ! $inspection->started_at) {
            $changes['started_at'] = now();
        }

        if ($derived === DigitalInspection::STATUS_COMPLETED) {
            $changes['started_at'] = $inspection->started_at ?? now();
            $changes['completed_at'] = $inspection->completed_at ?? now();
        }

        // Reopened: a completion stamp would otherwise claim a finished sheet.
        if ($derived !== DigitalInspection::STATUS_COMPLETED && $inspection->completed_at) {
            $changes['completed_at'] = null;
        }

        if ($changes !== []) {
            $inspection->forceFill($changes)->save();
        }
    }

    /** One line of plain English for why it sits where it does. */
    public static function explain(?DigitalInspection $inspection): string
    {
        if (! $inspection || ! $inspection->exists) {
            return 'Set once the inspection is saved and the checklist is worked through.';
        }

        return match (self::derive($inspection)) {
            DigitalInspection::STATUS_CANCELLED => 'This inspection was cancelled.',
            DigitalInspection::STATUS_COMPLETED => 'Every checklist item has a result.',
            DigitalInspection::STATUS_WIP => 'Some items are answered, some are still pending.',
            default => 'No checklist item has been answered yet.',
        };
    }
}
