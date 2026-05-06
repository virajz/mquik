<?php

namespace App\Modules\WorkshopDepartmentMaster\Models;

use App\Modules\WorkshopDepartmentMaster\Database\Factories\WorkshopDepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopDepartmentMaster extends Model
{
    use HasFactory;

    protected $table = 'workshop_departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): WorkshopDepartmentMasterFactory
    {
        return WorkshopDepartmentMasterFactory::new();
    }
}
