<?php

namespace App\Modules\JobCard\Models;

use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardInventoryItem extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_MISSING = 'missing';

    public const STATUS_DAMAGED = 'damaged';

    protected $table = 'job_card_inventory_items';

    protected $guarded = [];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function vehicleInventoryItem(): BelongsTo
    {
        return $this->belongsTo(VehicleInventoryItemMaster::class, 'vehicle_inventory_item_id');
    }

    public function damageType(): BelongsTo
    {
        return $this->belongsTo(DamageTypeMaster::class, 'damage_type_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_MISSING => 'Missing',
            self::STATUS_DAMAGED => 'Damaged',
        ];
    }
}
