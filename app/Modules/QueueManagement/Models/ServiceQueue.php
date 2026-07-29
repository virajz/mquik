<?php

namespace App\Modules\QueueManagement\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\QueueManagement\Database\Factories\ServiceQueueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceQueue extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_WAITING = 'waiting';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'service_queues';

    protected $guarded = [];

    protected $casts = [
        'is_high_priority' => 'boolean',
        'promised_delivery_at' => 'datetime',
        'expected_completion_at' => 'datetime',
        'kept_at' => 'datetime',
        'work_started_at' => 'datetime',
        'work_ended_at' => 'datetime',
    ];

    protected static array $searchableFields = ['queue_no', 'job_description', 'notes'];

    protected static function newFactory(): ServiceQueueFactory
    {
        return ServiceQueueFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->queue_no === null) {
                $row->forceFill([
                    'queue_no' => 'Q-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_WAITING => 'Waiting',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_READY => 'Ready',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function queueTypes(): array
    {
        return [
            'ac_service' => 'AC Service',
            'alignment_balancing' => 'Alignment / Balancing',
            'pdi' => 'PDI (Pre-Delivery Inspection)',
            'car_wash' => 'Car Wash',
            'detailing' => 'Detailing',
        ];
    }

    /** @return array<string, string> */
    public static function screenViews(): array
    {
        return [
            'upcoming' => 'Upcoming Vehicle',
            'arrived' => 'Arrived Vehicle',
            'ready' => 'Ready Vehicle',
        ];
    }

    /** @return array<string, string> */
    public static function orderingModes(): array
    {
        return [
            'fifo' => 'FIFO',
            'priority' => 'Priority Based',
        ];
    }

    /** @return array<string, string> */
    public static function highPriorityReasons(): array
    {
        return [
            'customer_waiting' => 'Customer Waiting',
            'vip_customer' => 'VIP Customer',
            'delivery_commitment' => 'Vehicle Delivery Commitment',
            'management_instruction' => 'Management Instruction',
            'customer_emergency' => 'Customer Emergency',
        ];
    }

    /** @return array<string, string> */
    public static function reworkReasons(): array
    {
        return [
            'customer_complaint' => 'Customer Complaint',
            'advisor_rejected' => 'Service Advisor Rejected',
            'dirty' => 'Dirty',
            'missed_area' => 'Missed Cleaning Area',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function delayReasons(): array
    {
        return [
            'water_supply' => 'Water Supply Issue',
            'power_failure' => 'Power Failure',
            'technician_unavailable' => 'Technician Not Available',
            'high_workload' => 'High Work Load',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function pauseReasons(): array
    {
        return [
            'equipment_failure' => 'Equipment Failure',
            'water_shortage' => 'Water Shortage',
            'power_failure' => 'Electric Power Failure',
        ];
    }

    /** Waiting minutes: kept for service → work started. */
    public function waitingMinutes(): ?int
    {
        if (! $this->kept_at || ! $this->work_started_at) {
            return null;
        }

        return (int) $this->kept_at->diffInMinutes($this->work_started_at);
    }

    /** Washing/service minutes: work started → ended. */
    public function serviceMinutes(): ?int
    {
        if (! $this->work_started_at || ! $this->work_ended_at) {
            return null;
        }

        return (int) $this->work_started_at->diffInMinutes($this->work_ended_at);
    }

    /** Total TAT minutes: kept → ended. */
    public function tatMinutes(): ?int
    {
        if (! $this->kept_at || ! $this->work_ended_at) {
            return null;
        }

        return (int) $this->kept_at->diffInMinutes($this->work_ended_at);
    }

    /** Whether the vehicle was completed on or before its promised/expected time. */
    public function isOnTime(): bool
    {
        $target = $this->expected_completion_at ?? $this->promised_delivery_at;

        return $this->work_ended_at !== null && $target !== null && $this->work_ended_at->lte($target);
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function hpRequestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'hp_requested_by_id');
    }

    public function hpRequestedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'hp_requested_to_id');
    }
}
