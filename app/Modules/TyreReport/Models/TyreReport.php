<?php

namespace App\Modules\TyreReport\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\TyreReport\Database\Factories\TyreReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TyreReport extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    /** The five wheel positions, in walk-around order. */
    public const POSITIONS = [
        'front_left' => 'Front Left',
        'front_right' => 'Front Right',
        'rear_left' => 'Rear Left',
        'rear_right' => 'Rear Right',
        'spare' => 'Spare Wheel',
    ];

    public const CONDITION_OK = 'ok';

    public const CONDITION_REPAIR = 'repair';

    public const CONDITION_REPLACE = 'replace';

    protected $table = 'tyre_reports';

    protected $guarded = [];

    protected $casts = [
        'reported_on' => 'date',
        'odometer_km' => 'integer',
    ];

    protected static array $searchableFields = ['report_no', 'recommendation', 'notes', 'customer.first_name', 'customer.last_name', 'customer.phone', 'customerVehicle.registration_no'];

    protected static function newFactory(): TyreReportFactory
    {
        return TyreReportFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $report) {
            if ($report->report_no === null) {
                $report->forceFill([
                    'report_no' => 'TR-'.str_pad((string) $report->id, 5, '0', STR_PAD_LEFT),
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

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'inspected_by_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TyreReportLine::class)->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    public static function conditions(): array
    {
        return [
            self::CONDITION_OK => 'OK',
            self::CONDITION_REPAIR => 'Repair',
            self::CONDITION_REPLACE => 'Replace',
        ];
    }

    /** How many wheels need replacing — the headline the advisor acts on. */
    public function replaceCount(): int
    {
        return $this->lines->where('condition', self::CONDITION_REPLACE)->count();
    }
}
