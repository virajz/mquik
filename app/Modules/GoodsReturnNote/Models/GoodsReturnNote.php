<?php

namespace App\Modules\GoodsReturnNote\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsReturnNote\Database\Factories\GoodsReturnNoteFactory;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReturnNote extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';

    public const STATUS_FULLY_ACCEPTED = 'fully_accepted';

    public const STATUS_REWORK_IN_PROGRESS = 'rework_in_progress';

    public const STATUS_REPLACEMENT_IN_PROGRESS = 'replacement_in_progress';

    public const STATUS_COUNTER_PROPOSAL = 'counter_proposal';

    public const STATUS_CN_ADJUSTED = 'cn_adjusted';

    public const STATUS_DN_ADJUSTED = 'dn_adjusted';

    public const STATUS_REPLACEMENT_ADJUSTED = 'replacement_adjusted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'goods_return_notes';

    protected $guarded = [];

    protected $casts = [
        'recovery_amount' => 'decimal:2',
        'tat_custom_days' => 'integer',
        'reminder_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['return_no', 'notes'];

    protected static function newFactory(): GoodsReturnNoteFactory
    {
        return GoodsReturnNoteFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->return_no === null) {
                $row->forceFill(['return_no' => 'GRTN-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Open = still being worked / not settled or closed. @return list<string> */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_REQUESTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_PARTIALLY_ACCEPTED,
            self::STATUS_REWORK_IN_PROGRESS,
            self::STATUS_REPLACEMENT_IN_PROGRESS,
            self::STATUS_COUNTER_PROPOSAL,
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_PARTIALLY_ACCEPTED => 'Partially Accepted',
            self::STATUS_FULLY_ACCEPTED => 'Fully Accepted',
            self::STATUS_REWORK_IN_PROGRESS => 'Rework In Progress',
            self::STATUS_REPLACEMENT_IN_PROGRESS => 'Replacement In Progress',
            self::STATUS_COUNTER_PROPOSAL => 'Counter Proposal',
            self::STATUS_CN_ADJUSTED => 'CN Adjusted',
            self::STATUS_DN_ADJUSTED => 'DN Adjusted',
            self::STATUS_REPLACEMENT_ADJUSTED => 'Replacement Adjusted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function returnTypes(): array
    {
        return ['regular' => 'Regular', 'warranty' => 'Warranty'];
    }

    /** @return array<string, string> */
    public static function claimTypes(): array
    {
        return [
            'outside_labour_warranty' => 'Outside Labour Warranty Claim (Labour + Parts)',
            'parts_warranty' => 'Parts Warranty Claim',
        ];
    }

    /** Both the labour-side and parts-side reasons in one list. @return array<string, string> */
    public static function returnReasons(): array
    {
        return [
            // Labour / workmanship
            'workmanship_failure' => 'Workmanship Failure',
            'incomplete_work' => 'Incomplete Work',
            'wrong_repair' => 'Wrong Repair',
            'material_failure' => 'Material Failure',
            'quality_standard_failure' => 'Quality Standard Failure',
            'excess_billing' => 'Excess Billing',
            'duplicate_billing' => 'Duplicate Billing',
            'incorrect_rate' => 'Incorrect Rate',
            'unauthorized_work' => 'Unauthorized Work',
            // Parts
            'defective_part' => 'Defective Part',
            'wrong_part_supplied' => 'Wrong Part Supplied',
            'damaged_part' => 'Damaged Part',
            'manufacturing_defect' => 'Manufacturing Defect',
            'premature_failure' => 'Premature Failure',
            'functional_failure' => 'Functional Failure',
            'incorrect_performance' => 'Incorrect Performance',
            'physical_damage_supply' => 'Physical Damage During Supply',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return ['within_warranty' => 'Within Warranty', 'warranty_expired' => 'Warranty Expired'];
    }

    /** @return array<string, string> */
    public static function warrantyPeriods(): array
    {
        return ['1_month' => '1 Month', '3_months' => '3 Months', '6_months' => '6 Months', '12_months' => '12 Months'];
    }

    /** @return array<string, string> */
    public static function reworkTypes(): array
    {
        return ['refit' => 'Refit', 'repair_again' => 'Repair Again', 'repaint' => 'Repaint'];
    }

    /** @return array<string, string> */
    public static function tatOptions(): array
    {
        return ['one_day' => '1 Day', 'two_days' => '2 Days', 'three_days' => '3 Days', 'custom' => 'Custom TAT'];
    }

    /** @return array<string, string> */
    public static function counterProposals(): array
    {
        return [
            'rework_free' => 'Rework Free of Cost',
            'shared_cost' => 'Shared Cost Settlement / Discount Offer',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'warranty_expired' => 'Warranty Expired',
            'physical_damage' => 'Physical Damage',
            'improper_installation' => 'Improper Installation',
            'customer_misuse' => 'Customer Misuse',
            'third_party_repair' => 'Third Party Repair',
            'work_not_done_by_vendor' => 'Work Not Done By Vendor',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return ['daily' => 'Daily', 'every_2_days' => 'Every 2 Days', 'custom' => 'Custom'];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality_service' => 'Quality Service',
            'quality_product' => 'Quality Product',
            'price' => 'Reasonable Price',
            'delivery' => 'Ontime Delivery',
            'support' => 'Handhold Support',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'transport_company_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReturnNoteItem::class, 'goods_return_note_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GoodsReturnNoteAttachment::class, 'goods_return_note_id')->orderBy('sequence_no');
    }
}
