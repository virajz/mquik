<?php

namespace App\Modules\VpoApproval\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpoApprovalItem extends Model
{
    protected $table = 'vpo_approval_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'qty_approved' => 'decimal:2',
        'rate_approved' => 'decimal:2',
        'discount_approved' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'last_purchase_date' => 'date',
        'part_approved' => 'boolean',
        'sequence_no' => 'integer',
    ];

    public function approval(): BelongsTo
    {
        return $this->belongsTo(VpoApproval::class, 'vpo_approval_id');
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

    public function vehicleVariant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariantMaster::class, 'vehicle_variant_id');
    }
}
