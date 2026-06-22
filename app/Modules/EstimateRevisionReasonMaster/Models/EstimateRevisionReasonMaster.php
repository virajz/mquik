<?php

namespace App\Modules\EstimateRevisionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EstimateRevisionReasonMaster\Database\Factories\EstimateRevisionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateRevisionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'estimate_revision_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): EstimateRevisionReasonMasterFactory
    {
        return EstimateRevisionReasonMasterFactory::new();
    }
}
