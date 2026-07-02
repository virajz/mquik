<?php

namespace App\Modules\RegularSalesInvoice\Models;

use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularSalesInvoiceItem extends Model
{
    protected $table = 'regular_sales_invoice_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'decimal:2',
        'cost_rate' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }
}
