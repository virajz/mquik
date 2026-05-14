<?php

namespace App\Modules\JobCard\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\JobCard\Database\Factories\JobCardFactory;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCard extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_AWAITING_PARTS = 'awaiting_parts';

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'job_cards';

    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'promised_at' => 'datetime',
        'closed_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'terms_accepted' => 'boolean',
    ];

    protected static array $searchableFields = ['job_card_no', 'suggested_services', 'notes'];

    protected static function newFactory(): JobCardFactory
    {
        return JobCardFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->job_card_no === null) {
                $row->forceFill([
                    'job_card_no' => 'JC-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function gateEvent(): BelongsTo
    {
        return $this->belongsTo(GateInOut::class, 'gate_event_id');
    }

    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repeat_of_job_card_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_technician_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(JobCardComplaint::class, 'job_card_id')->orderBy('sequence_no');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(JobCardInventoryItem::class, 'job_card_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(JobCardPhoto::class, 'job_card_id')->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_AWAITING_PARTS => 'Awaiting Parts',
            self::STATUS_AWAITING_APPROVAL => 'Awaiting Approval',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fuelLevels(): array
    {
        return [
            'empty' => 'Empty',
            'quarter' => '1/4',
            'half' => '1/2',
            'three_quarter' => '3/4',
            'full' => 'Full',
        ];
    }
}
