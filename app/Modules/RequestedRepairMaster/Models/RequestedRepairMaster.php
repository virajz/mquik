<?php

namespace App\Modules\RequestedRepairMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\RequestedRepairMaster\Database\Factories\RequestedRepairMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestedRepairMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'requested_repairs';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): RequestedRepairMasterFactory
    {
        return RequestedRepairMasterFactory::new();
    }
}
