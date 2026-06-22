<?php

namespace App\Modules\WorkOrderHoldReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\WorkOrderHoldReasonMaster\Database\Factories\WorkOrderHoldReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderHoldReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'work_order_hold_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): WorkOrderHoldReasonMasterFactory
    {
        return WorkOrderHoldReasonMasterFactory::new();
    }
}
