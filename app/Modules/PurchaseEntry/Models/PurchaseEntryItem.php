<?php

namespace App\Modules\PurchaseEntry\Models;

use App\Modules\ItemRejectionReasonMaster\Models\ItemRejectionReasonMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseEntryItem extends Model
{
    protected $table = 'purchase_entry_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(ItemRejectionReasonMaster::class, 'rejection_reason_id');
    }
}
