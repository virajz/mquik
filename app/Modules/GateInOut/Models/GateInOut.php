<?php

namespace App\Modules\GateInOut\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Models\User;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GateInOut\Database\Factories\GateInOutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateInOut extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ANPR = 'anpr';

    protected $table = 'gate_events';

    protected $guarded = [];

    protected $casts = [
        'gated_at' => 'datetime',
    ];

    protected static array $searchableFields = ['gate_event_no', 'registration_no', 'notes'];

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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    public static function directions(): array
    {
        return [
            self::DIRECTION_IN => 'In',
            self::DIRECTION_OUT => 'Out',
        ];
    }
}
