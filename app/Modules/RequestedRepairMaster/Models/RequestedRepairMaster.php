<?php

namespace App\Modules\RequestedRepairMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\RequestedRepairMaster\Database\Factories\RequestedRepairMasterFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RequestedRepairMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'requested_repairs';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): RequestedRepairMasterFactory
    {
        return RequestedRepairMasterFactory::new();
    }

    /**
     * Departments this repair is offered in. Empty means "all".
     */
    /** The complaint group this repair belongs to — fills the category on a job card. */
    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintTypeMaster::class, 'complaint_type_id');
    }

    public function workshopDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkshopDepartmentMaster::class,
            'requested_repair_workshop_department',
            'requested_repair_id',
            'workshop_department_id',
        )->withTimestamps();
    }
}
