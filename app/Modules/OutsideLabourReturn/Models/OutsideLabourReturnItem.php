<?php

namespace App\Modules\OutsideLabourReturn\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One returned part or labour line on a warranty claim, carrying its own
 * before/after photo evidence and the OL bill it came from.
 */
class OutsideLabourReturnItem extends Model
{
    protected $table = 'outside_labour_return_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function itemTypes(): array
    {
        return ['spare' => 'Spare / Part', 'labour' => 'Labour'];
    }

    /** @return array<string, string> */
    public static function materialConditions(): array
    {
        return ['new' => 'New', 'used' => 'Used', 'unused' => 'Unused', 'open_box' => 'Open Box'];
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourReturn::class, 'outside_labour_return_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourBill::class, 'outside_labour_bill_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function spareBrand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
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
