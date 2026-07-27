<?php

namespace App\Modules\OutsideLabourRejectionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\OutsideLabourRejectionReasonMaster\Database\Factories\OutsideLabourRejectionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutsideLabourRejectionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'outside_labour_rejection_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): OutsideLabourRejectionReasonMasterFactory
    {
        return OutsideLabourRejectionReasonMasterFactory::new();
    }
}
