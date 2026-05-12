<?php

namespace App\Modules\JobCardPendingReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\JobCardPendingReasonMaster\Database\Factories\JobCardPendingReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardPendingReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'job_card_pending_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): JobCardPendingReasonMasterFactory
    {
        return JobCardPendingReasonMasterFactory::new();
    }
}
