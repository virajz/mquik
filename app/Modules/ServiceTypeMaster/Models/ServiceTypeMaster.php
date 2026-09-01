<?php

namespace App\Modules\ServiceTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ServiceTypeMaster\Database\Factories\ServiceTypeMasterFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'service_types';

    protected $guarded = [];

    protected $casts = [
        'is_insurance' => 'boolean',
        'requires_advisor' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    protected static function newFactory(): ServiceTypeMasterFactory
    {
        return ServiceTypeMasterFactory::new();
    }
}
