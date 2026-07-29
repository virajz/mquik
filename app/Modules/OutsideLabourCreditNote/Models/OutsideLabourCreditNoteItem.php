<?php

namespace App\Modules\OutsideLabourCreditNote\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One labour line on a credit / debit note, pointing at the OL bill it adjusts.
 */
class OutsideLabourCreditNoteItem extends Model
{
    protected $table = 'outside_labour_credit_note_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourCreditNote::class, 'outside_labour_credit_note_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourBill::class, 'outside_labour_bill_id');
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
