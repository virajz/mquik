<?php

namespace App\Modules\JobCard\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JobCardComplaint extends Model
{
    protected $table = 'job_card_complaints';

    protected $guarded = [];

    protected $casts = [
        'reported_at' => 'datetime',
        'is_repeat_job' => 'boolean',
        'is_resolved' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (self $row) {
            JobCardHistoryRecorder::record(
                (int) $row->job_card_id,
                JobCardHistoryEvent::TYPE_COMPLAINT_ADDED,
                'Complaint: '.Str::limit($row->description, 80),
                ['complaint_id' => $row->id, 'severity' => $row->severity],
            );
        });

        static::updated(function (self $row) {
            $changed = $row->getChanges();
            if (isset($changed['is_resolved']) && $row->is_resolved && ! $row->getOriginal('is_resolved')) {
                JobCardHistoryRecorder::record(
                    (int) $row->job_card_id,
                    JobCardHistoryEvent::TYPE_COMPLAINT_RESOLVED,
                    'Complaint resolved: '.Str::limit($row->description, 80),
                    ['complaint_id' => $row->id],
                );
            }
        });
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }

    public function standardObservation(): BelongsTo
    {
        return $this->belongsTo(StandardObservationMaster::class, 'standard_observation_id');
    }
}
