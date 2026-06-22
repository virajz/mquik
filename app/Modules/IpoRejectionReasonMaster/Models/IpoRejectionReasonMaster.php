<?php

namespace App\Modules\IpoRejectionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\IpoRejectionReasonMaster\Database\Factories\IpoRejectionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpoRejectionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'ipo_rejection_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): IpoRejectionReasonMasterFactory
    {
        return IpoRejectionReasonMasterFactory::new();
    }
}
