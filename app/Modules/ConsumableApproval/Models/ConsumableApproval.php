<?php

namespace App\Modules\ConsumableApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ConsumableApproval\Database\Factories\ConsumableApprovalFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumableApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'consumable_approvals';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static array $searchableFields = ['request_no', 'notes'];

    protected static function newFactory(): ConsumableApprovalFactory
    {
        return ConsumableApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->request_no === null) {
                $row->forceFill(['request_no' => 'CA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function consumableCategories(): array
    {
        return [
            'paint' => 'Paint Consumables',
            'va' => 'VA Consumables',
            'workshop' => 'Workshop Consumables',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function approvalResponses(): array
    {
        return ['justification_required' => 'Justification Required', 'other' => 'Other'];
    }

    /** @return array<string, string> */
    public static function lossDamageTypes(): array
    {
        return [
            'transit_damage' => 'Transit Damage',
            'manufacturing_fault' => 'Manufacturing Fault',
            'unloading_damage' => 'Unloading Damage',
            'storage_damage' => 'Storage Damage',
            'fitment_damage' => 'Fitment Damage',
            'date_expired' => 'Date Expired',
            'leakage' => 'Leakage',
            'quality_rejection' => 'Quality Rejection',
            'evaporation_loss' => 'Evaporation Loss',
            'trial_testing_loss' => 'Trial or Testing Loss',
            'theft_loss' => 'Theft Loss',
            'goodwill' => 'Goodwill',
            'one_side_warranty' => 'One-side Warranty (Mquik FOC)',
            'other' => 'Other',
        ];
    }

    /** Loss types that count as damaged material. @return list<string> */
    public static function damagedLossTypes(): array
    {
        return ['transit_damage', 'manufacturing_fault', 'unloading_damage', 'storage_damage', 'fitment_damage', 'leakage', 'quality_rejection'];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function approvalAuthority(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'approval_authority_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsumableApprovalItem::class, 'consumable_approval_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ConsumableApprovalAttachment::class, 'consumable_approval_id')->orderBy('sequence_no');
    }
}
