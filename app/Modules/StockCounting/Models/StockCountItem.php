<?php

namespace App\Modules\StockCounting\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One counted spare on a stock count: system stock vs physical stock, the
 * resulting variance (`diff_qty`) and, when they disagree, the reason.
 */
class StockCountItem extends Model
{
    protected $table = 'stock_count_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'system_stock' => 'decimal:2',
        'physical_stock' => 'decimal:2',
        'diff_qty' => 'decimal:2',
        'net_total' => 'decimal:2',
        'purchase_date' => 'date',
        'entry_at' => 'datetime',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function varianceReasons(): array
    {
        return [
            'data_entry_error' => 'Data Entry Error',
            'wrong_issue' => 'Wrong Issue',
            'wrong_barcode' => 'Wrong Barcode',
            'damaged_parts' => 'Damaged Parts',
            'lost_item' => 'Lost Item',
            'expired_item' => 'Expired Item',
            'theft' => 'Theft',
            'duplicate_entry' => 'Duplicate Entry',
            'no_purchase_invoice' => 'No Purchase Invoice',
            'forgot_sales_issue' => 'Forgot Sales Issue',
            'technical_error' => 'Technical Error',
            'packing_difference' => 'Packing Difference',
            'vendor_short_supply' => 'Vendor Short Supply',
            'cn_pending' => 'CN Pending',
            'issue_pending' => 'Issue Pending',
            'unknown' => 'Unknown',
        ];
    }

    /** @return array<string, string> */
    public static function sparesConditions(): array
    {
        return [
            'broken' => 'Broken',
            'rusted' => 'Rusted',
            'expired' => 'Expired',
            'water_damage' => 'Water Damage',
            'transit_damage' => 'Transit Damage',
            'manufacturing_defect' => 'Manufacturing Defect',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
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
