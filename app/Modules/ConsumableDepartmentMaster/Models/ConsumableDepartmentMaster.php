<?php

namespace App\Modules\ConsumableDepartmentMaster\Models;

use App\Modules\ConsumableDepartmentMaster\Database\Factories\ConsumableDepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableDepartmentMaster extends Model
{
    use HasFactory;

    protected $table = 'consumable_departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ConsumableDepartmentMasterFactory
    {
        return ConsumableDepartmentMasterFactory::new();
    }
}
