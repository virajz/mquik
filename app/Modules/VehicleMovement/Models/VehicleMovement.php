<?php

namespace App\Modules\VehicleMovement\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VehicleMovement\Database\Factories\VehicleMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMovement extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const TYPE_INWARD = 'inward';

    public const TYPE_OUTWARD = 'outward';

    public const JOB_PENDING = 'pending';

    public const JOB_COMPLETED = 'completed';

    public const JOB_CANCELLED = 'cancelled';

    protected $table = 'vehicle_movements';

    protected $guarded = [];

    protected $casts = [
        'entry_at' => 'datetime',
        'exit_at' => 'datetime',
    ];

    protected static array $searchableFields = ['movement_no', 'number_plate', 'notes'];

    protected static function newFactory(): VehicleMovementFactory
    {
        return VehicleMovementFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->movement_no === null) {
                $row->forceFill(['movement_no' => 'IO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Turnaround in minutes between entry and exit, or null if still on premises. */
    public function tatMinutes(): ?int
    {
        if ($this->entry_at === null || $this->exit_at === null) {
            return null;
        }

        return (int) $this->entry_at->diffInMinutes($this->exit_at);
    }

    /** Human-friendly TAT (e.g. "2h 15m"), or null. */
    public function tatLabel(): ?string
    {
        $minutes = $this->tatMinutes();
        if ($minutes === null) {
            return null;
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }

    /** @return array<string, string> */
    public static function movementTypes(): array
    {
        return [self::TYPE_INWARD => 'Inward', self::TYPE_OUTWARD => 'Outward'];
    }

    /** @return array<string, string> */
    public static function jobStatuses(): array
    {
        return [
            self::JOB_PENDING => 'Pending',
            self::JOB_COMPLETED => 'Completed',
            self::JOB_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function parkingSlots(): array
    {
        return [
            'slot_1' => 'Slot No. 1',
            'slot_2' => 'Slot No. 2',
            'slot_3' => 'Slot No. 3',
            'outside_gate' => 'Outside of Gate',
        ];
    }

    /** @return array<string, string> */
    public static function gates(): array
    {
        return ['gate_1' => 'Gate No. 1', 'gate_2' => 'Gate No. 2'];
    }

    /** @return array<string, string> */
    public static function outwardTypes(): array
    {
        return [
            'trial_run' => 'Trial Run',
            'outside_labour' => 'Outside Labour Work',
            'final_delivery' => 'Final Delivery',
            'fuel_filling' => 'Fuel Filling',
            'puc_inspection' => 'PUC Inspection',
            'rto_passing' => 'RTO Passing',
        ];
    }

    /** @return array<string, string> */
    public static function driverTypes(): array
    {
        return [
            'customer_self' => 'Customer Self',
            'customer_representative' => 'Customer Representative',
            'workshop_staff' => 'Workshop Staff',
            'vendor_driver' => 'Vendor Driver',
            'towing_driver' => 'Towing Driver',
        ];
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function gatePassApproval(): BelongsTo
    {
        return $this->belongsTo(GatePassApproval::class, 'gate_pass_approval_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'delivered_by_id');
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'security_guard_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VehicleMovementAttachment::class, 'vehicle_movement_id')->orderBy('sequence_no');
    }
}
