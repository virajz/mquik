<?php

namespace App\Modules\ConsumableDepartmentMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ConsumableDepartmentMaster\Database\Factories\ConsumableDepartmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableDepartmentMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'consumable_departments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ConsumableDepartmentMasterFactory
    {
        return ConsumableDepartmentMasterFactory::new();
    }
}
