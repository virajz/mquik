<?php

namespace App\Modules\AdvisorFeedback\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvisorFeedback\Database\Factories\AdvisorFeedbackFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorFeedback extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'advisor_feedbacks';

    protected $guarded = [];

    protected $casts = [
        'cooperative_rating' => 'integer',
        'timely_approvals_rating' => 'integer',
        'payment_committed_rating' => 'integer',
        'professional_rating' => 'integer',
        'prefer_again_rating' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static array $searchableFields = ['feedback_no', 'invoice_reference', 'notes'];

    protected static function newFactory(): AdvisorFeedbackFactory
    {
        return AdvisorFeedbackFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->feedback_no === null) {
                $row->forceFill(['feedback_no' => 'AF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Average of the five 1–5 answers that were given, or null. */
    public function averageRating(): ?float
    {
        $scores = array_filter([
            $this->cooperative_rating,
            $this->timely_approvals_rating,
            $this->payment_committed_rating,
            $this->professional_rating,
            $this->prefer_again_rating,
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
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** The five feedback questions, keyed by their rating column. @return array<string, string> */
    public static function questions(): array
    {
        return [
            'cooperative_rating' => 'Was the customer cooperative during the service?',
            'timely_approvals_rating' => 'Did the customer provide timely approvals?',
            'payment_committed_rating' => 'Was payment received as committed?',
            'professional_rating' => 'Did the customer communicate professionally?',
            'prefer_again_rating' => 'Would you prefer handling this customer again?',
        ];
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
}
