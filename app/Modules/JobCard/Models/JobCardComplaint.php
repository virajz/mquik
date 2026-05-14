<?php

namespace App\Modules\JobCard\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardComplaint extends Model
{
    protected $table = 'job_card_complaints';

    protected $guarded = [];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }
}
