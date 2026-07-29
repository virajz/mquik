<?php

namespace App\Modules\ExcessStockApproval\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One excess-stock spare line; qty × rate is its excess value.
 */
class ExcessStockApprovalItem extends Model
{
    protected $table = 'excess_stock_approval_items';

    protected $guarded = [];

    protected $casts = ['quantity' => 'decimal:2', 'rate' => 'decimal:2', 'sequence_no' => 'integer'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ExcessStockApproval::class, 'excess_stock_approval_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }
}
