<?php

namespace App\Modules\EstimateTemplateMaster\Models;

use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateTemplateItem extends Model
{
    protected $table = 'estimate_template_items';

    protected $guarded = [];

    protected $casts = [
        'default_qty' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EstimateTemplateMaster::class, 'estimate_template_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }
}
