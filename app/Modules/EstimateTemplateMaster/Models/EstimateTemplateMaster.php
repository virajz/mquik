<?php

namespace App\Modules\EstimateTemplateMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EstimateTemplateMaster\Database\Factories\EstimateTemplateMasterFactory;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateTemplateMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'estimate_templates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): EstimateTemplateMasterFactory
    {
        return EstimateTemplateMasterFactory::new();
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateTemplateItem::class, 'estimate_template_id')->orderBy('sequence_no');
    }
}
