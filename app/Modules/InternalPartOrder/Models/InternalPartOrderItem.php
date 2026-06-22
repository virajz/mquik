<?php

namespace App\Modules\InternalPartOrder\Models;

use App\Modules\ReturnTypeMaster\Models\ReturnTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalPartOrderItem extends Model
{
    protected $table = 'internal_part_order_items';

    protected $guarded = [];

    protected $casts = [
        'is_alternate' => 'boolean',
        'qty_requested' => 'decimal:2',
        'qty_issued' => 'decimal:2',
        'qty_returned' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(InternalPartOrder::class, 'internal_part_order_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function returnType(): BelongsTo
    {
        return $this->belongsTo(ReturnTypeMaster::class, 'return_type_id');
    }
}
