<?php

namespace App\Modules\ChequeBounceReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ChequeBounceReasonMaster\Database\Factories\ChequeBounceReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeBounceReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'cheque_bounce_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ChequeBounceReasonMasterFactory
    {
        return ChequeBounceReasonMasterFactory::new();
    }
}
