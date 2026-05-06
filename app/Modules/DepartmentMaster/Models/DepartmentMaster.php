<?php

namespace App\Modules\DepartmentMaster\Models;

use App\Modules\DepartmentMaster\Database\Factories\DepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentMaster extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DepartmentMasterFactory
    {
        return DepartmentMasterFactory::new();
    }
}
