<?php

namespace App\Modules\GoodsHandover\Models;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One issued / returned spare on a handover, with its QC outcome and a photo.
 */
class GoodsHandoverItem extends Model
{
    protected $table = 'goods_handover_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function materialConditions(): array
    {
        return [
            'new' => 'New', 'used' => 'Used', 'refurbished' => 'Refurbished',
            'repairable' => 'Repairable', 'repaired' => 'Repaired', 'scrap' => 'Scrap',
        ];
    }

    /** @return array<string, string> */
    public static function physicalVerifications(): array
    {
        return [
            'excess_qty' => 'Excess Qty',
            'less_qty' => 'Less Qty',
            'physical_damage' => 'Physical Damage',
            'wrong_part' => 'Wrong Part',
            'manufacturing_defect' => 'Manufacturing Defect',
            'missing_item' => 'Missing Item',
            'expired_material' => 'Expired Material',
            'packaging_damage' => 'Packaging Damage',
        ];
    }

    /** @return array<string, string> */
    public static function damageTypes(): array
    {
        return ['old_damage' => 'Old Damage', 'fitment_damage' => 'Fitment Damage'];
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(GoodsHandover::class, 'goods_handover_id');
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

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }
}
