<?php

namespace App\Modules\Proforma\Models;

use App\Modules\ItemRejectionReasonMaster\Models\ItemRejectionReasonMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaItem extends Model
{
    protected $table = 'proforma_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:2',
        'cost_rate' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class, 'proforma_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
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
