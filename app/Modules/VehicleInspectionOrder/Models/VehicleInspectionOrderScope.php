<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
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

    protected $casts = [
        'sequence_no' => 'integer',
        'is_additional' => 'boolean',
        'run_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    /** @return array<string, string> */
    public static function workStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function isRunning(): bool
    {
        return $this->work_status === self::STATUS_IN_PROGRESS && $this->run_started_at !== null;
    }

    /** Accumulated time plus the current running segment, in whole seconds. */
    public function elapsedSeconds(): int
    {
        $base = (int) $this->duration_seconds;

        return $this->isRunning()
            ? $base + max(0, now()->getTimestamp() - $this->run_started_at->getTimestamp())
            : $base;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function requestedRepair(): BelongsTo
    {
        return $this->belongsTo(RequestedRepairMaster::class, 'requested_repair_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
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
