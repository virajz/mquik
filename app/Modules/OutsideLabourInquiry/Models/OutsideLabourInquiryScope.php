<?php

namespace App\Modules\OutsideLabourInquiry\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of what the outside contractor is being asked to do — a labour
 * service, a job description, or a customer complaint (any combination).
 */
class OutsideLabourInquiryScope extends Model
{
    protected $table = 'outside_labour_inquiry_scopes';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourInquiry::class, 'outside_labour_inquiry_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }
}
