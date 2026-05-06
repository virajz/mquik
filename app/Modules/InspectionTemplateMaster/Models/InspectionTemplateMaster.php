<?php

namespace App\Modules\InspectionTemplateMaster\Models;

use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Database\Factories\InspectionTemplateMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InspectionTemplateMaster extends Model
{
    use HasFactory;

    protected $table = 'inspection_templates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function appliesToOptions(): array
    {
        return ['pms', 'tyre', 'bodyshop', 'basic', 'custom'];
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            InspectionItemMaster::class,
            'inspection_template_items',
            'inspection_template_id',
            'inspection_item_id',
        )
            ->withPivot(['position', 'is_required'])
            ->withTimestamps()
            ->orderBy('inspection_template_items.position');
    }

    protected static function newFactory(): InspectionTemplateMasterFactory
    {
        return InspectionTemplateMasterFactory::new();
    }
}
