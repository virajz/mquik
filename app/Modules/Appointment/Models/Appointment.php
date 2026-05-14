<?php

namespace App\Modules\Appointment\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Database\Factories\AppointmentFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public const CHANNEL_APP = 'app';

    public const CHANNEL_WEBSITE = 'website';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PHONE_CALL = 'phone_call';

    protected $table = 'appointments';

    protected $guarded = [];

    protected $casts = [
        'appointment_at' => 'datetime',
        'requires_pickup' => 'boolean',
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

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_NO_SHOW => 'No-show',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function channels(): array
    {
        return [
            self::CHANNEL_APP => 'Customer App',
            self::CHANNEL_WEBSITE => 'Website',
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_PHONE_CALL => 'Phone Call',
        ];
    }
}
