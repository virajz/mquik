<?php

namespace App\Modules\IpoCancellationReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\IpoCancellationReasonMaster\Database\Factories\IpoCancellationReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpoCancellationReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'ipo_cancellation_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): IpoCancellationReasonMasterFactory
    {
        return IpoCancellationReasonMasterFactory::new();
    }
}
