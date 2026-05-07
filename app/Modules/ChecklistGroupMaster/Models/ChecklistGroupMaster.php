<?php

namespace App\Modules\ChecklistGroupMaster\Models;

use App\Concerns\Auditable;
use App\Modules\ChecklistGroupMaster\Database\Factories\ChecklistGroupMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistGroupMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'checklist_groups';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ChecklistGroupMasterFactory
    {
        return ChecklistGroupMasterFactory::new();
    }
}
