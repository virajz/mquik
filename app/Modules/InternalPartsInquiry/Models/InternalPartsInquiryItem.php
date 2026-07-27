<?php

namespace App\Modules\InternalPartsInquiry\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalPartsInquiryItem extends Model
{
    protected $table = 'internal_parts_inquiry_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate_before_tax' => 'decimal:2',
        'needed_by_date' => 'datetime',
        'sequence_no' => 'integer',
    ];

    /** Stock availability of a requested part. @return array<string, string> */
    public static function stockStatuses(): array
    {
        return [
            'available' => 'Available',
            'not_available' => 'Not Available',
            'reserved' => 'Reserved',
            'issued' => 'Issued',
            'ordered' => 'Ordered',
            'in_transit' => 'In Transit',
            'backorder' => 'Backorder',
        ];
    }

    /** Whether the line is the primary part, an alternate part, or an alternate brand. @return array<string, string> */
    public static function alternativeOptions(): array
    {
        return [
            'primary' => 'Primary Part',
            'alternate_part' => 'Alternate Part',
            'alternate_brand' => 'Alternate Brand',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(InternalPartsInquiry::class, 'internal_parts_inquiry_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function spareBrand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
    }

    public function partType(): BelongsTo
    {
        return $this->belongsTo(PartTypeMaster::class, 'part_type_id');
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
