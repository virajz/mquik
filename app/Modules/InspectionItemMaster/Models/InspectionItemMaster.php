<?php

namespace App\Modules\InspectionItemMaster\Models;

use App\Concerns\Auditable;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Database\Factories\InspectionItemMasterFactory;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InspectionItemMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'inspection_items';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function checkTypes(): array
    {
        return ['visual', 'measurement', 'yes_no', 'rating'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(InspectionItemGroupMaster::class, 'inspection_item_group_id');
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(
            InspectionTemplateMaster::class,
            'inspection_template_items',
            'inspection_item_id',
            'inspection_template_id',
        )->withPivot(['position', 'is_required'])->withTimestamps();
    }

    protected static function newFactory(): InspectionItemMasterFactory
    {
        return InspectionItemMasterFactory::new();
    }
}
