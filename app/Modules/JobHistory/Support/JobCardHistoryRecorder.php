<?php

namespace App\Modules\JobHistory\Support;

use App\Modules\JobHistory\Models\JobCardHistoryEvent;

class JobCardHistoryRecorder
{
    /**
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
            'event_type' => $eventType,
            'actor_user_id' => auth()->id(),
            'summary' => $summary,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
