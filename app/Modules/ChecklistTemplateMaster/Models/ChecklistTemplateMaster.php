<?php

namespace App\Modules\ChecklistTemplateMaster\Models;

use App\Concerns\Auditable;
use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Database\Factories\ChecklistTemplateMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistTemplateMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'checklist_templates';

    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public static function appliesToOptions(): array
    {
        return ['job_card', 'pickup', 'delivery', 'claim', 'generic'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChecklistGroupMaster::class, 'checklist_group_id');
    }

    protected static function newFactory(): ChecklistTemplateMasterFactory
    {
        return ChecklistTemplateMasterFactory::new();
    }
}
