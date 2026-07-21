<?php

namespace App\Modules\PickupDrop\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What the customer reported when the vehicle was collected. */
class PickupDropComplaint extends Model
{
    protected $table = 'pickup_drop_complaints';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function pickupDrop(): BelongsTo
    {
        return $this->belongsTo(PickupDrop::class);
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
