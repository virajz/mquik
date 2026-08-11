<?php

namespace App\Modules\JobHistory\Support;

use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use Illuminate\Support\Facades\DB;

class JobCardHistoryRecorder
{
    /**
     * Record an event against a job card.
     *
     * The card's vehicle is resolved automatically so the event also lands on
     * the vehicle timeline — callers don't have to know about that dimension.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public static function record(
        int $jobCardId,
        string $eventType,
        string $summary,
        ?array $payload = null,
    ): JobCardHistoryEvent {
        return JobCardHistoryEvent::create([
            'job_card_id' => $jobCardId,
            'customer_vehicle_id' => self::vehicleForJobCard($jobCardId),
            'event_type' => $eventType,
            'actor_user_id' => auth()->id(),
            'summary' => $summary,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Record an event against a vehicle that may not have a job card yet —
     * an appointment booking, a gate arrival, a pickup being scheduled.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public static function recordForVehicle(
        ?int $vehicleId,
        string $eventType,
        string $summary,
        ?array $payload = null,
        ?int $jobCardId = null,
        ?\DateTimeInterface $occurredAt = null,
    ): ?JobCardHistoryEvent {
        if (! $vehicleId && ! $jobCardId) {
            return null;
        }

        return JobCardHistoryEvent::create([
            'job_card_id' => $jobCardId,
            'customer_vehicle_id' => $vehicleId ?: self::vehicleForJobCard($jobCardId),
            'event_type' => $eventType,
            'actor_user_id' => auth()->id(),
            'summary' => $summary,
            'payload' => $payload,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /** Cheap lookup — avoids hydrating the whole JobCard model on every event. */
    protected static function vehicleForJobCard(?int $jobCardId): ?int
    {
        if (! $jobCardId) {
            return null;
        }

        return DB::table('job_cards')->where('id', $jobCardId)->value('customer_vehicle_id');
    }
}
