<?php

namespace App\Modules\OutsideLabourBill\Models;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line on an outside-labour bill — the job card / vehicle it covers, and
 * the billed vs verified amount.
 */
class OutsideLabourBillItem extends Model
{
    protected $table = 'outside_labour_bill_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'verified_amount' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourBill::class, 'outside_labour_bill_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }
}
