<?php

namespace App\Modules\Appointment\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Database\Factories\AppointmentFactory;
use App\Modules\Appointment\Support\AppointmentStatus;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
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

    public const STATUS_VEHICLE_COLLECTED = 'vehicle_collected';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_RESCHEDULED = 'rescheduled';

    protected $table = 'appointments';

    protected $guarded = [];

    protected $casts = [
        'appointment_at' => 'datetime',
        'rescheduled_from_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['appointment_no', 'pickup_address', 'pickup_contact_phone', 'notes', 'customer.first_name', 'customer.last_name', 'customer.phone', 'customerVehicle.registration_no'];

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
        // Status is never written by a form, so anything that could change what
        // it derives to — a pending reason, a cancellation — recomputes it here.
        static::saved(function (self $appointment) {
            AppointmentStatus::refresh($appointment);
        });

        static::created(function (self $appointment) {
            if ($appointment->appointment_no === null) {
                $appointment->forceFill([
                    'appointment_no' => 'APT-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            JobCardHistoryRecorder::recordForVehicle(
                $appointment->customer_vehicle_id,
                JobCardHistoryEvent::TYPE_APPOINTMENT_BOOKED,
                'Appointment '.$appointment->appointment_no.' booked',
                ['source_id' => $appointment->id],
                $appointment->job_card_id ?? null,
                $appointment->appointment_at,
            );
        });

        // Forward-sync identity + pickup address to linked pickup/drops that haven't
        // been collected yet, so a correction to the appointment isn't stranded on an
        // in-flight pickup. Schedule/status/direction stay the pickup's own.
        static::updated(function (self $appointment) {
            $syncKeys = ['customer_id', 'customer_vehicle_id', 'pickup_address_id', 'pickup_address'];
            if (empty(array_intersect($syncKeys, array_keys($appointment->getChanges())))) {
                return;
            }

            $appointment->pickupDrops()
                ->whereNotIn('status', [
                    PickupDrop::STATUS_VEHICLE_COLLECTED,
                    PickupDrop::STATUS_VEHICLE_DELIVERED,
                    PickupDrop::STATUS_COMPLETED,
                    PickupDrop::STATUS_CANCELLED,
                ])
                ->update([
                    'customer_id' => $appointment->customer_id,
                    'customer_vehicle_id' => $appointment->customer_vehicle_id,
                    'pickup_address_id' => $appointment->pickup_address_id,
                    'pickup_address' => $appointment->pickup_address,
                ]);
        });
    }

    /** Jobs this booking is for, as opposed to what the customer complained about. */
    public function services(): HasMany
    {
        return $this->hasMany(AppointmentService::class)->orderBy('sequence_no');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'pickup_address_id');
    }

    /** Leaf region (usually an area) of the collection address. */
    public function pickupRegion(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'pickup_region_id');
    }

    /** Leaf region of the return address. */
    public function dropRegion(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'drop_region_id');
    }

    /** The pickup address resolved live from the linked saved address, else the free-text snapshot. */
    public function resolvedPickupAddress(): ?string
    {
        return $this->pickup_address_id ? $this->pickupAddress?->fullAddress() : $this->pickup_address;
    }

    /** The pickup contact resolved live: an explicit override, else the customer's current phone. */
    public function resolvedContactPhone(): ?string
    {
        return $this->pickup_contact_phone ?: $this->customer?->phone;
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

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'appointment_id');
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
            self::STATUS_VEHICLE_COLLECTED => 'Vehicle Collected',
            self::STATUS_ARRIVED => 'Arrived',
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

        // The derived ladder already knows about collection and everything after
        // it, so the driver's own stages only add detail while the booking has
        // not moved yet — otherwise a stale "Vehicle Delivered" would talk over
        // an appointment the workshop has already taken in.
        $notStartedYet = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_RESCHEDULED];

        if (! in_array($this->status, $notStartedYet, true)) {
            return $own;
        }

        $job = $this->pickupDrops->sortByDesc('id')->first();

        // Only an early, in-flight driver stage refines it; the job's own
        // pending/completed/cancelled are not the customer's view.
        $early = [PickupDrop::STATUS_DRIVER_ASSIGNED, PickupDrop::STATUS_DRIVER_ON_THE_WAY];

        if (! $job || ! in_array($job->status, $early, true)) {
            return $own;
        }

        return PickupDrop::statuses()[$job->status];
    }
}
