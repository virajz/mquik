<?php

namespace App\Modules\CustomerFeedback\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerFeedback\Database\Factories\CustomerFeedbackFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerFeedback extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_SENT = 'sent';

    public const STATUS_SATISFIED = 'satisfied';

    public const STATUS_DISSATISFIED = 'dissatisfied';

    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'customer_feedbacks';

    protected $guarded = [];

    protected $casts = [
        'staff_experience_rating' => 'integer',
        'service_experience_rating' => 'integer',
        'service_rating' => 'integer',
        'price_rating' => 'integer',
        'ontime_delivery_rating' => 'integer',
        'would_recommend' => 'boolean',
        'follow_up_custom_days' => 'integer',
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static array $searchableFields = ['feedback_no', 'invoice_reference', 'notes'];

    protected static function newFactory(): CustomerFeedbackFactory
    {
        return CustomerFeedbackFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->feedback_no === null) {
                $row->forceFill(['feedback_no' => 'CF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Average of the five 1–5 rating answers that were given, or null. */
    public function averageRating(): ?float
    {
        $scores = array_filter([
            $this->staff_experience_rating,
            $this->service_experience_rating,
            $this->service_rating,
            $this->price_rating,
            $this->ontime_delivery_rating,
        ], fn ($v) => $v !== null);

        if ($scores === []) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => 'Sent',
            self::STATUS_SATISFIED => 'Satisfied',
            self::STATUS_DISSATISFIED => 'Dissatisfied',
            self::STATUS_UNDER_INVESTIGATION => 'Under Investigation',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function followUpSchedules(): array
    {
        return [
            '4_days' => '4 Days After Billing',
            '7_days' => '7 Days After Billing',
            '15_days' => '15 Days After Billing',
            '30_days' => '30 Days After Billing',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function followUpCategories(): array
    {
        return [
            'service_quality' => 'Service Quality Check',
            'vehicle_performance' => 'Vehicle Performance Observation',
            'feedback_collection' => 'Feedback Collection',
        ];
    }

    /** @return array<string, string> */
    public static function followUpModes(): array
    {
        return ['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'Email', 'call' => 'Telephonic Call'];
    }

    /** @return array<string, string> */
    public static function followUpAttempts(): array
    {
        return ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'final' => 'Final'];
    }

    /** @return array<string, string> */
    public static function vehicleObservations(): array
    {
        return [
            'same_issue_repeat' => 'Same Issue Repeat Again',
            'monitoring' => 'Customer Monitoring the Vehicle',
            'need_more_time' => 'Need More Time for Observation',
            'new_issue' => 'New Issue Arised',
            'running_normally' => 'Vehicle Running Normally',
        ];
    }

    /** @return array<string, string> */
    public static function feedbackSources(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'website' => 'Website',
            'google_review' => 'Google Review',
            'mobile_app' => 'Mobile App',
            'reception' => 'Reception Desk / Tablet',
        ];
    }

    /** @return array<string, string> */
    public static function feedbackCategories(): array
    {
        return ['complaint' => 'Complaint', 'suggestion' => 'Suggestion', 'praise' => 'Praise / Appreciation'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function gatePassApproval(): BelongsTo
    {
        return $this->belongsTo(GatePassApproval::class, 'gate_pass_approval_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CustomerFeedbackAttachment::class, 'customer_feedback_id')->orderBy('sequence_no');
    }
}
