<?php

namespace App\Modules\PickupDrop\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDrop\Database\Factories\PickupDropFactory;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupDrop extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const DIRECTION_PICKUP = 'pickup';

    public const DIRECTION_DROP = 'drop';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DRIVER_ASSIGNED = 'driver_assigned';

    public const STATUS_DRIVER_ON_THE_WAY = 'driver_on_the_way';

    public const STATUS_VEHICLE_COLLECTED = 'vehicle_collected';

    public const STATUS_VEHICLE_DELIVERED = 'vehicle_delivered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OTP_OPTIONAL = 'optional';

    public const OTP_MANDATORY = 'mandatory';

    public const LEG_PICKUP = 'pickup';

    public const LEG_DROP = 'drop';

    protected $table = 'pickup_drops';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'rescheduled_from_at' => 'datetime',
        'pickup_otp_verified_at' => 'datetime',
        'delivery_otp_verified_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'distance_charge' => 'decimal:2',
    ];

    protected static array $searchableFields = ['pickup_drop_no', 'pickup_address', 'drop_address', 'contact_phone', 'notes'];

    protected static function newFactory(): PickupDropFactory
    {
        return PickupDropFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->pickup_drop_no === null) {
                $row->forceFill([
                    'pickup_drop_no' => 'PD-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'driver_employee_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_employee_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'vendor_courier_id');
    }

    public function pickupDropOption(): BelongsTo
    {
        return $this->belongsTo(PickupDropOptionMaster::class, 'pickup_drop_option_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlotMaster::class, 'time_slot_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function distanceSlab(): BelongsTo
    {
        return $this->belongsTo(DistanceSlabMaster::class, 'distance_slab_id');
    }

    public function pickupRegion(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'pickup_region_id');
    }

    public function dropRegion(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'drop_region_id');
    }

    public function pendingReason(): BelongsTo
    {
        return $this->belongsTo(PendingReasonMaster::class, 'pending_reason_id');
    }

    public function rescheduleReason(): BelongsTo
    {
        return $this->belongsTo(PendingReasonMaster::class, 'reschedule_reason_id');
    }

    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(CancelReasonMaster::class, 'cancel_reason_id');
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateMaster::class, 'checklist_template_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(PickupDropComplaint::class)->orderBy('sequence_no');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PickupDropDocument::class)->orderBy('sequence_no');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PickupDropPhoto::class)->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    public static function directions(): array
    {
        return [
            self::DIRECTION_PICKUP => 'Pickup',
            self::DIRECTION_DROP => 'Drop',
        ];
    }

    /**
     * The seven-state lifecycle from the CSV. Appointment surfaces the middle
     * four as its own displayed status, so these labels are the single source
     * of truth for what a customer is told.
     *
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DRIVER_ASSIGNED => 'Driver Assigned',
            self::STATUS_DRIVER_ON_THE_WAY => 'Driver on the Way',
            self::STATUS_VEHICLE_COLLECTED => 'Vehicle Collected',
            self::STATUS_VEHICLE_DELIVERED => 'Vehicle Delivered',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** The stages that mean a driver is actively handling the vehicle. */
    public static function driverStages(): array
    {
        return [
            self::STATUS_DRIVER_ASSIGNED,
            self::STATUS_DRIVER_ON_THE_WAY,
            self::STATUS_VEHICLE_COLLECTED,
            self::STATUS_VEHICLE_DELIVERED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function otpModes(): array
    {
        return [
            self::OTP_OPTIONAL => 'Optional',
            self::OTP_MANDATORY => 'Mandatory',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function legs(): array
    {
        return [
            self::LEG_PICKUP => 'At pickup',
            self::LEG_DROP => 'At drop',
        ];
    }
}
