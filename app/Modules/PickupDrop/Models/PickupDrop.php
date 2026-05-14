<?php

namespace App\Modules\PickupDrop\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Database\Factories\PickupDropFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupDrop extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const DIRECTION_PICKUP = 'pickup';

    public const DIRECTION_DROP = 'drop';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PICKED = 'picked';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'pickup_drops';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['pickup_drop_no', 'address', 'contact_phone', 'notes'];

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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'vendor_courier_id');
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
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_PICKED => 'Picked',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
