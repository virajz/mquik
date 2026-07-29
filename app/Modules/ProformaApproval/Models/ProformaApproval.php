<?php

namespace App\Modules\ProformaApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ProformaApproval\Database\Factories\ProformaApprovalFactory;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProformaApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_UNDER_PREPARATION = 'under_preparation';

    public const STATUS_ADVISOR_PENDING = 'advisor_approval_pending';

    public const STATUS_STORE_PENDING = 'store_approval_pending';

    public const STATUS_ADMIN_PENDING = 'admin_approval_pending';

    public const STATUS_RETURN_FOR_CORRECTION = 'return_for_correction';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_CONVERTED = 'converted_to_invoice';

    protected $table = 'proforma_approvals';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'prepared_at' => 'datetime',
        'store_approved_at' => 'datetime',
        'advisor_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected static array $searchableFields = ['approval_no', 'proforma_reference', 'notes'];

    protected static function newFactory(): ProformaApprovalFactory
    {
        return ProformaApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill(['approval_no' => 'PFA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_UNDER_PREPARATION => 'Under Preparation',
            self::STATUS_ADVISOR_PENDING => 'Advisor Approval Pending',
            self::STATUS_STORE_PENDING => 'Store Approval Pending',
            self::STATUS_ADMIN_PENDING => 'Admin Approval Pending',
            self::STATUS_RETURN_FOR_CORRECTION => 'Return for Correction',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_CONVERTED => 'Converted to Invoice',
        ];
    }

    /** @return array<string, string> */
    public static function stages(): array
    {
        return [
            'stage_1' => 'Stage 1 — Prepared (Billing Executive)',
            'stage_2' => 'Stage 2 — Store In-charge Approval',
            'stage_3' => 'Stage 3 — Service Advisor Approval',
            'stage_4' => 'Stage 4 — Admin Approval',
            'stage_5' => 'Stage 5 — Customer / Insurance Approval',
            'stage_6' => 'Stage 6 — Invoice Conversion',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'billing_executive' => 'Billing Executive',
            'store_incharge' => 'Store In-charge',
            'service_advisor' => 'Service Advisor',
            'workshop_admin' => 'Workshop Manager / Admin / Owner',
            'customer_insurance' => 'Customer / Insurance Company',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    /** @return array<string, string> */
    public static function checkpointRoles(): array
    {
        return [
            'billing_executive' => 'Billing Executive',
            'store_incharge' => 'Store In-charge',
            'service_advisor' => 'Service Advisor',
            'admin' => 'Admin',
        ];
    }

    /** @return array<string, string> */
    public static function billingCheckpoints(): array
    {
        return [
            'ipo_qty_mismatch' => 'IPO Qty Mismatch',
            'ipo_missing_item' => 'IPO Missing Item',
            'ipo_excess_item' => 'IPO Excess Item',
            'fwo_missing_item' => 'FWO Missing Item',
            'fwo_excess_item' => 'FWO Excess Item',
            'mrp_mismatch' => 'MRP Mismatch',
            'labour_rate_mismatch' => 'Labour Rate / Price List Mismatch',
        ];
    }

    /** @return array<string, string> */
    public static function storeCheckpoints(): array
    {
        return [
            'ipo_qty_mismatch' => 'IPO Qty Mismatch',
            'ipo_missing_item' => 'IPO Missing Item',
            'ipo_excess_item' => 'IPO Excess Item',
            'mrp_mismatch' => 'MRP Mismatch',
            'group_sales_amt' => 'Inventory Group-wise Sales Amt',
            'group_purchase_amt' => 'Inventory Group-wise Purchase Amt',
            'group_pnl_amt' => 'Inventory Group-wise P&L Amt',
            'brand_sales_amt' => 'Spares Brand-wise Sales Amt',
            'brand_purchase_amt' => 'Spares Brand-wise Purchase Amt',
            'brand_pnl_amt' => 'Spares Brand-wise P&L Amt',
            'vendor_sales_amt' => 'Vendor-wise Sales Amt',
            'vendor_purchase_amt' => 'Vendor-wise Purchase Amt',
            'vendor_pnl_amt' => 'Vendor-wise P&L Amt',
        ];
    }

    /** @return array<string, string> */
    public static function advisorCheckpoints(): array
    {
        return [
            'ipo_qty_mismatch' => 'IPO Qty Mismatch',
            'ipo_missing_item' => 'IPO Missing Item',
            'ipo_excess_item' => 'IPO Excess Item',
            'fwo_missing_item' => 'FWO Missing Item',
            'fwo_excess_item' => 'FWO Excess Item',
            'mrp_mismatch' => 'MRP Mismatch',
            'labour_rate_mismatch' => 'Labour Rate / Price List Mismatch',
            'estimate_amt_mismatch' => 'Estimate Amt Mismatch',
            'promised_date_discrepancy' => 'Promised Date Discrepancy',
            'discount_amt' => 'Discount Amt',
            'loss_amt' => 'Loss Amt',
            'warranty_amt' => 'Warranty Amt',
            'billing_details' => 'Billing Details — Customer / Ins. Company',
        ];
    }

    /** @return array<string, string> */
    public static function adminCheckpoints(): array
    {
        return [
            'group_sales_amt' => 'Inventory Group-wise Sales Amt',
            'group_purchase_amt' => 'Inventory Group-wise Purchase Amt',
            'group_pnl_amt' => 'Inventory Group-wise P&L Amt',
            'brand_sales_amt' => 'Spares Brand-wise Sales Amt',
            'brand_purchase_amt' => 'Spares Brand-wise Purchase Amt',
            'brand_pnl_amt' => 'Spares Brand-wise P&L Amt',
            'estimate_amt_mismatch' => 'Estimate Amt Mismatch',
            'promised_date_discrepancy' => 'Promised Date Discrepancy',
            'discount_amt' => 'Discount Amt',
            'loss_amt' => 'Loss Amt',
            'warranty_amt' => 'Warranty Amt',
            'vendor_sales_amt' => 'Vendor-wise Sales Amt',
            'vendor_purchase_amt' => 'Vendor-wise Purchase Amt',
            'vendor_pnl_amt' => 'Vendor-wise P&L Amt',
        ];
    }

    /**
     * Checkpoint options for a role.
     *
     * @return array<string, string>
     */
    public static function checkpointsForRole(string $role): array
    {
        return match ($role) {
            'billing_executive' => self::billingCheckpoints(),
            'store_incharge' => self::storeCheckpoints(),
            'service_advisor' => self::advisorCheckpoints(),
            'admin' => self::adminCheckpoints(),
            default => [],
        };
    }

    /** Every valid checkpoint key across all roles. @return list<string> */
    public static function allCheckpointKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_keys(self::billingCheckpoints()),
            array_keys(self::storeCheckpoints()),
            array_keys(self::advisorCheckpoints()),
            array_keys(self::adminCheckpoints()),
        )));
    }

    /** @return array<string, string> */
    public static function lossReasons(): array
    {
        return ['damaged' => 'Damaged', 'defective' => 'Defective', 'date_expired' => 'Date Expired', 'leakage' => 'Leakage', 'other' => 'Other'];
    }

    /** @return array<string, string> */
    public static function missingReasons(): array
    {
        return [
            'spares_not_available' => 'Spares Not Available',
            'included_in_set' => 'Included in Set / Kit',
            'not_required' => 'Not Required',
            'back_order' => 'Back Order',
        ];
    }

    /** @return array<string, string> */
    public static function discountTypes(): array
    {
        return ['line' => 'Line Discount', 'scheme' => 'Scheme Discount (Package / Combo)', 'cash' => 'Cash Discount'];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'verbal_clarification' => 'Verbal Clarification',
            'incorrect_parts' => 'Incorrect Parts',
            'incorrect_labour' => 'Incorrect Labour',
            'rate_mismatch' => 'Rate Mismatch',
            'item_missing' => 'Item Missing',
            'qty_mismatch' => 'Qty Mismatch',
            'customer_approval_pending' => 'Customer Approval Pending',
            'correction' => 'Correction (item-wise)',
            'additional_note' => 'Additional Note',
        ];
    }

    /** @return array<string, string> */
    public static function checkpointStatuses(): array
    {
        return ['ok' => 'OK', 'flagged' => 'Flagged'];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function billingExecutive(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'billing_executive_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(ProformaApprovalCheckpoint::class, 'proforma_approval_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProformaApprovalAttachment::class, 'proforma_approval_id')->orderBy('sequence_no');
    }
}
