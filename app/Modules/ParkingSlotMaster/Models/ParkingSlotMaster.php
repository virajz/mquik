<?php

namespace App\Modules\ParkingSlotMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ParkingSlotMaster\Database\Factories\ParkingSlotMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSlotMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'parking_slots';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ParkingSlotMasterFactory
    {
        return ParkingSlotMasterFactory::new();
    }
}
