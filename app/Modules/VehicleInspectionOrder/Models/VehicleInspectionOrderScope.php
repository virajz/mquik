<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of what this order is inspecting. A line may point at a complaint,
 * a job description or a package — Service, Combo and AMC packages all live in
 * `service_packages` and differ only by their type.
 */
class VehicleInspectionOrderScope extends Model
{
    protected $table = 'vehicle_inspection_order_scopes';

    protected $guarded = [];

    protected $casts = ['sequence_no' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }
}
