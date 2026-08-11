<?php

namespace App\Modules\GateInOut\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Models\User;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Database\Factories\GateInOutFactory;
use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per *visit*: the inward leg opens it, the outward leg closes it.
 * TAT is the gap between the two, so a vehicle still on site is simply one
 * with no exited_at.
 */
class GateInOut extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ANPR = 'anpr';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'gate_visits';

    protected $guarded = [];

    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    protected static array $searchableFields = ['gate_event_no', 'registration_no', 'notes', 'customer.first_name'];

    protected static function newFactory(): GateInOutFactory
    {
        return GateInOutFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            // Reg-No standardisation: collapse whitespace and uppercase. The full
            // GJ 05 AA 1234 spacing is enforced upstream during data entry.
            if ($row->registration_no) {
                $row->registration_no = strtoupper(preg_replace('/\s+/', ' ', trim($row->registration_no)));
            }
        });

        static::created(function (self $row) {
            if ($row->gate_event_no === null) {
                $row->forceFill([
                    'gate_event_no' => 'GE-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            JobCardHistoryRecorder::recordForVehicle(
                $row->customer_vehicle_id,
                JobCardHistoryEvent::TYPE_VEHICLE_ARRIVED,
                'Vehicle arrived at gate ('.$row->gate_event_no.')',
                ['source_id' => $row->id],
                $row->job_card_id ?? null,
                $row->entered_at,
            );
        });

        // Gate-out closes the visit — the other end of the vehicle's timeline.
        static::updated(function (self $row) {
            if (! array_key_exists('exited_at', $row->getChanges()) || ! $row->exited_at) {
                return;
            }

            JobCardHistoryRecorder::recordForVehicle(
                $row->customer_vehicle_id,
                JobCardHistoryEvent::TYPE_VEHICLE_DEPARTED,
                'Vehicle left the gate ('.$row->gate_event_no.')',
                ['source_id' => $row->id],
                $row->job_card_id ?? null,
                $row->exited_at,
            );
        });
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function entryGate(): BelongsTo
    {
        return $this->belongsTo(GateMaster::class, 'entry_gate_id');
    }

    public function exitGate(): BelongsTo
    {
        return $this->belongsTo(GateMaster::class, 'exit_gate_id');
    }

    public function parkingSlot(): BelongsTo
    {
        return $this->belongsTo(ParkingSlotMaster::class, 'parking_slot_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'delivered_by_id');
    }

    /** The security guard who signed the vehicle out. */
    public function exitBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'exit_by_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** Vehicles that came in but have not gone out again. */
    public function scopeStillInside(Builder $query): Builder
    {
        return $query->whereNull('exited_at')->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Turnaround time in minutes, or null while the vehicle is still on site. */
    public function tatMinutes(): ?int
    {
        if ($this->entered_at === null || $this->exited_at === null) {
            return null;
        }

        return (int) $this->entered_at->diffInMinutes($this->exited_at);
    }

    /** "3h 20m" — TAT for display, or an em dash while still inside. */
    public function tatForHumans(): string
    {
        $minutes = $this->tatMinutes();

        if ($minutes === null) {
            return '—';
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Why the vehicle is leaving. A fixed workshop process vocabulary, so it
     * lives here rather than as another single-consumer master.
     *
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_MANUAL => 'Manual',
            self::SOURCE_ANPR => 'ANPR Camera',
        ];
    }
}
