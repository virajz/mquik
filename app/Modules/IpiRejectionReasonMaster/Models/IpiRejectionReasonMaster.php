<?php

namespace App\Modules\IpiRejectionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\IpiRejectionReasonMaster\Database\Factories\IpiRejectionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpiRejectionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'ipi_rejection_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): IpiRejectionReasonMasterFactory
    {
        return IpiRejectionReasonMasterFactory::new();
    }
}
