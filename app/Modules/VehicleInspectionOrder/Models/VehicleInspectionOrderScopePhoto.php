<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One before- or after-shot against a work scope line. Several of each are
 * normal: a clutch job is not evidenced by a single photo.
 */
class VehicleInspectionOrderScopePhoto extends Model
{
    public const STAGE_BEFORE = 'before';

    public const STAGE_AFTER = 'after';

    protected $table = 'vehicle_inspection_order_scope_photos';

    protected $guarded = [];

    public function scope(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrderScope::class, 'vehicle_inspection_order_scope_id');
    }
}
