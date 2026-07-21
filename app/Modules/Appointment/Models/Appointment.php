<?php

namespace App\Modules\Appointment\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Database\Factories\AppointmentFactory;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_RESCHEDULED = 'rescheduled';

    protected $table = 'appointments';

    protected $guarded = [];

    protected $casts = [
        'appointment_at' => 'datetime',
        'rescheduled_from_at' => 'datetime',
    ];

    protected static array $searchableFields = ['appointment_no', 'pickup_address', 'pickup_contact_phone', 'notes'];

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }

    /**
     * Stamp APT-XXXXX onto the row right after insert. Done in the model so
     * imports, console commands, factory rows — every creation path gets it.
     */
    protected static function booted(): void
    {
        static::created(function (self $appointment) {
            if ($appointment->appointment_no === null) {
                $appointment->forceFill([
                    'appointment_no' => 'APT-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_technician_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlotMaster::class, 'time_slot_id');
    }

    public function bookingChannel(): BelongsTo
    {
        return $this->belongsTo(BookingChannelMaster::class, 'booking_channel_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function pickupDropOption(): BelongsTo
    {
        return $this->belongsTo(PickupDropOptionMaster::class, 'pickup_drop_option_id');
    }

    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(CancelReasonMaster::class, 'cancel_reason_id');
    }

    public function pendingReason(): BelongsTo
    {
        return $this->belongsTo(PendingReasonMaster::class, 'pending_reason_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(AppointmentComplaint::class)->orderBy('sequence_no');
    }

    /** Pickup/Drop jobs raised off this appointment — the source of the driver stages. */
    public function pickupDrops(): HasMany
    {
        return $this->hasMany(PickupDrop::class, 'appointment_id');
    }

    /** True when the chosen option means the workshop must collect the vehicle. */
    public function requiresPickup(): bool
    {
        return (bool) $this->pickupDropOption?->involves_pickup;
    }

    /** True when the chosen option means the workshop must return the vehicle. */
    public function requiresDrop(): bool
    {
        return (bool) $this->pickupDropOption?->involves_drop;
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_RESCHEDULED => 'Rescheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_NO_SHOW => 'No-show',
        ];
    }

    /**
     * The status the CSV asks to see. Driver stages (Driver Assigned → Vehicle
     * Delivered) are not stored here — they are read live off the linked
     * Pickup/Drop job so there is only ever one source of truth. A finished or
     * cancelled appointment always wins over whatever the driver is doing.
     */
    public function effectiveStatusLabel(): string
    {
        $own = self::statuses()[$this->status] ?? $this->status;

        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW], true)) {
            return $own;
        }

        $job = $this->pickupDrops->sortByDesc('id')->first();

        // Only an in-flight driver stage overrides the appointment's own state;
        // the job's own pending/completed/cancelled are not the customer's view.
        if (! $job || ! in_array($job->status, PickupDrop::driverStages(), true)) {
            return $own;
        }

        return PickupDrop::statuses()[$job->status];
    }
}
