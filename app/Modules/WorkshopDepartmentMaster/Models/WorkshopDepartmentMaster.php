<?php

namespace App\Modules\WorkshopDepartmentMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\WorkshopDepartmentMaster\Database\Factories\WorkshopDepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopDepartmentMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'workshop_departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): WorkshopDepartmentMasterFactory
    {
        return WorkshopDepartmentMasterFactory::new();
    }
}
