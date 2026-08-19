<?php

namespace App\Modules\GateInOut\Models;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One trip out and back during a visit — a trial run, a vendor drop, a fuel run.
 * Not the outward: the vehicle is still in our care until the visit itself ends.
 */
class GateVisitMovement extends Model
{
    public const PURPOSE_TRIAL_RUN = 'trial_run';

    public const PURPOSE_OUTSIDE_LABOUR = 'outside_labour';

    public const PURPOSE_FUEL = 'fuel';

    public const PURPOSE_RTO = 'rto';

    public const PURPOSE_CUSTOMER_REQUEST = 'customer_request';

    public const PURPOSE_OTHER = 'other';

    protected $table = 'gate_visit_movements';

    protected $guarded = [];

    protected $casts = [
        'out_at' => 'datetime',
        'in_at' => 'datetime',
        'expected_back_at' => 'datetime',
        'odometer_out' => 'integer',
        'odometer_in' => 'integer',
    ];

    /** @return array<string, string> */
    public static function purposes(): array
    {
        return [
            self::PURPOSE_TRIAL_RUN => 'Trial Run',
            self::PURPOSE_OUTSIDE_LABOUR => 'Outside Labour',
            self::PURPOSE_FUEL => 'Fuel',
            self::PURPOSE_RTO => 'RTO Work',
            self::PURPOSE_CUSTOMER_REQUEST => 'Customer Request',
            self::PURPOSE_OTHER => 'Other',
        ];
    }

    public function isOut(): bool
    {
        return $this->in_at === null;
    }

    /** Out longer than promised — what the floor needs chasing on. */
    public function isOverdue(): bool
    {
        return $this->isOut()
            && $this->expected_back_at !== null
            && $this->expected_back_at->isPast();
    }

    /** Minutes away — running while out, final once back. */
    public function elapsedMinutes(): int
    {
        if (! $this->out_at) {
            return 0;
        }

        return (int) $this->out_at->diffInMinutes($this->in_at ?? now());
    }

    /** "2h 15m" — the number a floor manager actually reads. */
    public function elapsedLabel(): string
    {
        $minutes = $this->elapsedMinutes();

        if ($minutes < 60) {
            return $minutes.'m';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        return $days > 0
            ? trim($days.'d '.$hours.'h')
            : trim($hours.'h '.$mins.'m');
    }

    /** How far past the promised return, if it is late. */
    public function overdueLabel(): ?string
    {
        if (! $this->isOverdue()) {
            return null;
        }

        $minutes = (int) $this->expected_back_at->diffInMinutes(now());

        return $minutes < 60
            ? $minutes.'m late'
            : intdiv($minutes, 60).'h '.($minutes % 60).'m late';
    }

    /** Distance covered on this trip, when both readings were taken. */
    public function distanceKm(): ?int
    {
        if ($this->odometer_out === null || $this->odometer_in === null) {
            return null;
        }

        return max(0, $this->odometer_in - $this->odometer_out);
    }

    /** @param  Builder<GateVisitMovement>  $query */
    public function scopeStillOut(Builder $query): Builder
    {
        return $query->whereNull('in_at');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GateInOut::class, 'gate_visit_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'driver_employee_id');
    }

    public function outGate(): BelongsTo
    {
        return $this->belongsTo(GateMaster::class, 'out_gate_id');
    }

    public function inGate(): BelongsTo
    {
        return $this->belongsTo(GateMaster::class, 'in_gate_id');
    }
}
