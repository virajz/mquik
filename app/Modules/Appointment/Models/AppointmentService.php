<?php

namespace App\Modules\Appointment\Models;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One job the customer is booking in for — either a job description from the
 * master, or a free-typed line for something not in it yet.
 */
class AppointmentService extends Model
{
    protected $guarded = [];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }
}
