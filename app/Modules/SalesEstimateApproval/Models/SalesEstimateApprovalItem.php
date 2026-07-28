<?php

namespace App\Modules\SalesEstimateApproval\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One estimate line under approval — a spare, labour or package — with the
 * approver's decision and depreciation by part category.
 */
class SalesEstimateApprovalItem extends Model
{
    protected $table = 'sales_estimate_approval_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'depreciation_percent' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function lineApprovals(): array
    {
        return [
            'repair' => 'Repair',
            'replace' => 'Replace',
            'remove_refit' => 'Remove & Refit',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'pending' => 'Approval Pending',
        ];
    }

    /** Part-category depreciation slabs. @return array<string, string> */
    public static function depreciationCategories(): array
    {
        return [
            'plastic' => 'Plastic',
            'metal' => 'Metal',
            'rubber' => 'Rubber',
            'glass' => 'Glass',
        ];
    }

    /** Line total after depreciation = qty × rate × (1 − dep%). */
    public function netAfterDepreciation(): float
    {
        $gross = (float) $this->quantity * (float) $this->unit_rate;

        return round($gross * (1 - (float) $this->depreciation_percent / 100), 2);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(SalesEstimateApproval::class, 'sales_estimate_approval_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
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
