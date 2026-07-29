<?php

namespace App\Modules\VehicleAmc\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleAmc\Database\Factories\VehicleAmcFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleAmc extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_RENEWED = 'renewed';

    public const STATUS_LOST = 'lost';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'vehicle_amcs';

    protected $guarded = [];

    protected $casts = [
        'services_limit' => 'integer',
        'services_availed' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static array $searchableFields = ['amc_no', 'notes'];

    protected static function newFactory(): VehicleAmcFactory
    {
        return VehicleAmcFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->amc_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'amc_no' => 'MQ/AMC/'.$fy.'/'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** Remaining services on the contract, or null when there is no limit. */
    public function servicesRemaining(): ?int
    {
        if ($this->services_limit === null) {
            return null;
        }

        return max(0, $this->services_limit - $this->services_availed);
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_RENEWED => 'Renewed',
            self::STATUS_LOST => 'Lost',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function packages(): array
    {
        return ['silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum'];
    }

    /** @return array<string, string> */
    public static function validities(): array
    {
        return ['12_months' => '12 Months', '24_months' => '24 Months', '36_months' => '36 Months'];
    }

    /** @return array<int, string> */
    public static function serviceLimits(): array
    {
        return [2 => '2 Services', 3 => '3 Services', 4 => '4 Services', 6 => '6 Services'];
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
            'refunded' => 'Refunded',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'sold_by_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VehicleAmcItem::class, 'vehicle_amc_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VehicleAmcAttachment::class, 'vehicle_amc_id')->orderBy('sequence_no');
    }
}
