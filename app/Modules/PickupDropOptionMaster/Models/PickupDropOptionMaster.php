<?php

namespace App\Modules\PickupDropOptionMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\PickupDropOptionMaster\Database\Factories\PickupDropOptionMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupDropOptionMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'pickup_drop_options';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'involves_pickup' => 'boolean',
        'involves_drop' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): PickupDropOptionMasterFactory
    {
        return PickupDropOptionMasterFactory::new();
    }
}
