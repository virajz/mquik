<?php

namespace App\Modules\GoodsReceipt\Models;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One received spare on a GRN, carrying its QC outcome, storage bin, per-line
 * approval and a spare photo.
 */
class GoodsReceiptItem extends Model
{
    protected $table = 'goods_receipt_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'part_approved' => 'boolean',
        'quantity_approved' => 'decimal:2',
        'rate_approved' => 'decimal:2',
        'discount_approved' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'last_purchase_date' => 'date',
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
            'ok' => 'OK',
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
        return [
            'transit' => 'Transit Damage',
            'manufacturing' => 'Manufacturing Damage',
            'unloading' => 'Unloading Damage',
            'storage' => 'Storage Damage',
            'fitment' => 'Fitment Damage',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function floorReceivedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'floor_received_by_id');
    }

    public function floorVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'floor_verified_by_id');
    }
}
