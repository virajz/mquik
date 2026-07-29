<?php

namespace App\Modules\PolicyRenewalFollowUp\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\PolicyRenewalFollowUp\Database\Factories\PolicyRenewalFollowUpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyRenewalFollowUp extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_RENEWED = 'renewed';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_LOST = 'lost_opportunity';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'policy_renewal_follow_ups';

    protected $guarded = [];

    protected $casts = [
        'policy_start_date' => 'date',
        'policy_end_date' => 'date',
        'renewal_premium' => 'decimal:2',
        'reminder_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'response_at' => 'datetime',
        'quote_shared_at' => 'datetime',
        'payment_received_at' => 'datetime',
        'policy_issued_at' => 'datetime',
    ];

    protected static array $searchableFields = ['follow_up_no', 'policy_number', 'renewal_reference', 'notes'];

    protected static function newFactory(): PolicyRenewalFollowUpFactory
    {
        return PolicyRenewalFollowUpFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->follow_up_no === null) {
                $row->forceFill(['follow_up_no' => 'IPR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Statuses that are still open (not renewed / lost / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_ASSIGNED, self::STATUS_QUOTATION_SENT, self::STATUS_OVERDUE];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_QUOTATION_SENT => 'Quotation Sent',
            self::STATUS_RENEWED => 'Renewed',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_LOST => 'Lost Opportunity',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'before_15_days' => 'Before 15 Days',
            'before_7_days' => 'Before 7 Days',
            'today' => 'Today',
            'after_1_week' => 'After 1 Week',
        ];
    }

    /** @return array<string, string> */
    public static function followUpAttempts(): array
    {
        return ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'final' => 'Final'];
    }

    /** @return array<string, string> */
    public static function followUpModes(): array
    {
        return ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email', 'call' => 'Telephonic Call'];
    }

    /** @return array<string, string> */
    public static function customerResponses(): array
    {
        return [
            'quote_requested' => 'Quote Requested',
            'comparing_policies' => 'Comparing Policies',
            'renewal_confirmed' => 'Renewal Confirmed',
            'callback_later' => 'Callback Later',
            'call_not_connected' => 'Call Not Connected',
            'call_not_received' => 'Call Not Received',
            'call_rejected' => 'Call Rejected',
            'wrong_number' => 'Wrong Number',
            'renewed_elsewhere' => 'Renewed Elsewhere',
            'vehicle_sold' => 'Vehicle Sold',
            'not_interested' => 'Not Interested',
            'no_response' => 'No Response',
        ];
    }

    /** @return array<string, string> */
    public static function lostReasons(): array
    {
        return [
            'lower_premium_elsewhere' => 'Lower Premium Elsewhere',
            'existing_agent' => 'Existing Insurance Agent',
            'direct_renewal' => 'Direct Renewal',
            'vehicle_sold' => 'Vehicle Sold',
            'not_interested' => 'Customer Not Interested',
            'financial_issue' => 'Financial Issue',
        ];
    }

    /** @return array<string, string> */
    public static function escalations(): array
    {
        return ['senior_executive' => 'Escalated to Senior Executive', 'insurance_manager' => 'Escalated to Insurance Manager'];
    }

    /** @return array<string, string> */
    public static function escalationReasons(): array
    {
        return ['complaint_raised' => 'Complaint Raised', 'vip_customer' => 'VIP Customer', 'high_value' => 'High Value'];
    }

    /** @return array<string, string> */
    public static function retentions(): array
    {
        return ['active' => 'Active', 'lost' => 'Lost', 'recovered' => 'Recovered'];
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function policyType(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicyTypeMaster::class, 'insurance_policy_type_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PolicyRenewalFollowUpAttachment::class, 'policy_renewal_follow_up_id')->orderBy('sequence_no');
    }
}
