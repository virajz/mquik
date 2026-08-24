<?php

namespace App\Modules\DocumentCollection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Database\Factories\DocumentCollectionFactory;
use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentCollection extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const REQUEST_CUSTOMER = 'customer';

    public const REQUEST_INSURANCE_CLAIM = 'insurance_claim';

    public const RETENTION_ACTIVE = 'active';

    public const RETENTION_ARCHIVE = 'archive';

    public const RETENTION_DELETE = 'delete';

    public const REMINDER_CUSTOM = 'custom';

    protected $table = 'document_collections';

    protected $guarded = [];

    protected $casts = [
        'reminder_custom_days' => 'integer',
        'retention_days' => 'integer',
        'retired_at' => 'datetime',
        'requested_at' => 'datetime',
        'received_at' => 'datetime',
        'entry_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    protected static array $searchableFields = [
        'doc_collection_no', 'policy_no', 'notes',
        'customer.first_name', 'customer.last_name', 'customer.phone',
        'customerVehicle.registration_no', 'customerVehicle.model.name', 'customerVehicle.model.brand.name',
        'jobCard.job_card_no',
    ];

    protected static function newFactory(): DocumentCollectionFactory
    {
        return DocumentCollectionFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->doc_collection_no === null) {
                $row->forceFill([
                    'doc_collection_no' => 'DC-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            JobCardHistoryRecorder::recordForVehicle(
                $row->customer_vehicle_id,
                JobCardHistoryEvent::TYPE_DOCUMENT_REQUESTED,
                'Documents requested ('.$row->doc_collection_no.')',
                ['source_id' => $row->id],
                $row->job_card_id ?? null,
                $row->requested_at,
            );
        });

        static::bootRetentionCascade();
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function requestTypes(): array
    {
        return [
            self::REQUEST_CUSTOMER => 'Customer',
            self::REQUEST_INSURANCE_CLAIM => 'Insurance Claim',
        ];
    }

    /** @return array<string, string> */
    public static function purposes(): array
    {
        return [
            'insurance_process' => 'Insurance Process Flow',
            'ownership_confirmation' => 'Vehicle Ownership Confirmation',
            'payment_limit' => 'Payment Limit Exceed',
            'repair_authorization' => 'Repair Authorization',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'every_2_days' => 'Every 2 Days',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function retentions(): array
    {
        return [
            'active' => 'Active',
            'archive' => 'Archive',
            'delete' => 'Auto-delete',
        ];
    }

    /** The chase log: every attempt at getting the documents in. */
    public function followUps(): HasMany
    {
        return $this->hasMany(DocumentCollectionFollowUp::class)->orderByDesc('followed_up_at');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentCollectionItem::class, 'document_collection_id')->orderBy('sequence_no');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(DocumentCollectionVerification::class, 'document_collection_id')->orderBy('sequence_no');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
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
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'created_by_advisor_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'collected_by_driver_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function policyType(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicyTypeMaster::class, 'insurance_policy_type_id');
    }

    public function claimType(): BelongsTo
    {
        return $this->belongsTo(ClaimTypeMaster::class, 'claim_type_id');
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateMaster::class, 'checklist_template_id');
    }

    public function verificationTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateMaster::class, 'verification_template_id');
    }

    public function missingDocumentReason(): BelongsTo
    {
        return $this->belongsTo(MissingDocumentReasonMaster::class, 'missing_document_reason_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(DocumentRejectionReasonMaster::class, 'rejection_reason_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    /**
     * Soft-deleting a collection takes its document lines with it, so a restore
     * brings back the whole checklist rather than an empty shell.
     */
    protected static function bootRetentionCascade(): void
    {
        static::deleted(function (self $row) {
            if ($row->isForceDeleting()) {
                return;
            }

            $row->items()->delete();
        });

        static::restored(function (self $row) {
            $row->items()->withTrashed()->restore();
        });
    }

    /**
     * When this collection becomes eligible for retention clean-up, or null if
     * it never does. The clock runs from the linked job card's closed_at — the
     * closest thing to "billed" the schema currently has. Swap this one method
     * when Document Collection gains a real invoice link.
     */
    public function retentionDueAt(): ?CarbonInterface
    {
        if ($this->retention !== self::RETENTION_DELETE || $this->retention_days === null) {
            return null;
        }

        $billedAt = $this->jobCard?->closed_at;

        return $billedAt?->copy()->addDays($this->retention_days);
    }

    /** True once the retention window has elapsed and nothing has retired it yet. */
    public function isRetentionDue(): bool
    {
        $due = $this->retentionDueAt();

        return $due !== null && $this->retired_at === null && $due->isPast();
    }

    /**
     * @return array<string, string>
     */
    public static function reminderIntervalDays(): array
    {
        return ['daily' => 1, 'every_2_days' => 2];
    }
}
