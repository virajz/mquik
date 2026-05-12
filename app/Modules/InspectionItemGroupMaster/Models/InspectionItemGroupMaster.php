<?php

namespace App\Modules\InspectionItemGroupMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InspectionItemGroupMaster\Database\Factories\InspectionItemGroupMasterFactory;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionItemGroupMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'inspection_item_groups';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItemMaster::class, 'inspection_item_group_id');
    }

    protected static function newFactory(): InspectionItemGroupMasterFactory
    {
        return InspectionItemGroupMasterFactory::new();
    }
}
