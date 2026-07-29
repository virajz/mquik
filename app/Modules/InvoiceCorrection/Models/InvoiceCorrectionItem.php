<?php

namespace App\Modules\InvoiceCorrection\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One correction line, carrying the old vs new value being changed.
 */
class InvoiceCorrectionItem extends Model
{
    protected $table = 'invoice_correction_items';

    protected $guarded = [];

    protected $casts = ['quantity' => 'decimal:2', 'rate' => 'decimal:2', 'sequence_no' => 'integer'];

    /** @return array<string, string> */
    public static function itemTypes(): array
    {
        return ['spare' => 'Spare / Part', 'labour' => 'Labour'];
    }

    public function correction(): BelongsTo
    {
        return $this->belongsTo(InvoiceCorrection::class, 'invoice_correction_id');
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
