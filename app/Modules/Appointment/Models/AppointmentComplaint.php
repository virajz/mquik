<?php

namespace App\Modules\Appointment\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the customer reported when booking — carried into the Job Card's own
 * complaint lines when the appointment is converted.
 */
class AppointmentComplaint extends Model
{
    protected $table = 'appointment_complaints';

    protected $guarded = [];

    protected $casts = [
        'sequence_no' => 'integer',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }
}
