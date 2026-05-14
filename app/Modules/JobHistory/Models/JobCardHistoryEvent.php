<?php

namespace App\Modules\JobHistory\Models;

use App\Models\User;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardHistoryEvent extends Model
{
    public const TYPE_CREATED = 'created';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_ADVISOR_CHANGED = 'advisor_changed';

    public const TYPE_TECHNICIAN_CHANGED = 'technician_changed';

    public const TYPE_COMPLAINT_ADDED = 'complaint_added';

    public const TYPE_COMPLAINT_RESOLVED = 'complaint_resolved';

    public const TYPE_INSPECTION_STARTED = 'inspection_started';

    public const TYPE_INSPECTION_COMPLETED = 'inspection_completed';

    public const TYPE_CANCELLED = 'cancelled';

    protected $table = 'job_card_history_events';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CREATED => 'Created',
            self::TYPE_STATUS_CHANGED => 'Status Changed',
            self::TYPE_ADVISOR_CHANGED => 'Advisor Changed',
            self::TYPE_TECHNICIAN_CHANGED => 'Technician Changed',
            self::TYPE_COMPLAINT_ADDED => 'Complaint Added',
            self::TYPE_COMPLAINT_RESOLVED => 'Complaint Resolved',
            self::TYPE_INSPECTION_STARTED => 'Inspection Started',
            self::TYPE_INSPECTION_COMPLETED => 'Inspection Completed',
            self::TYPE_CANCELLED => 'Job Card Cancelled',
        ];
    }
}
