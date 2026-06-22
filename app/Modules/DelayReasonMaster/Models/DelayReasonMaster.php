<?php

namespace App\Modules\DelayReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DelayReasonMaster\Database\Factories\DelayReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelayReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'delay_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): DelayReasonMasterFactory
    {
        return DelayReasonMasterFactory::new();
    }
}
