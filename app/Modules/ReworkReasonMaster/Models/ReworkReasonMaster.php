<?php

namespace App\Modules\ReworkReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ReworkReasonMaster\Database\Factories\ReworkReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReworkReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'rework_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ReworkReasonMasterFactory
    {
        return ReworkReasonMasterFactory::new();
    }
}
