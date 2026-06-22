<?php

namespace App\Modules\InspectionTemplateMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Database\Factories\InspectionTemplateMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InspectionTemplateMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'inspection_templates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public static function appliesToOptions(): array
    {
        return ['pms', 'tyre', 'bodyshop', 'basic', 'custom'];
    }

    /**
     * Inspection frequency (CSV row 11: Every Service / Every 5,000 km / Every 10,000 km).
     *
     * @return array<string, string>
     */
    public static function frequencies(): array
    {
        return [
            'every_service' => 'Every Service',
            '5000' => 'Every 5,000 km',
            '10000' => 'Every 10,000 km',
        ];
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
