<?php

namespace App\Modules\PickupDrop\Models;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One job this pickup/drop is for — a job description from the master, or a
 * free-typed line for something not in it yet.
 */
class PickupDropService extends Model
{
    protected $guarded = [];

    public function pickupDrop(): BelongsTo
    {
        return $this->belongsTo(PickupDrop::class);
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }
}
