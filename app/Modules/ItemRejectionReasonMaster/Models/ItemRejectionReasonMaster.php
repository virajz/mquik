<?php

namespace App\Modules\ItemRejectionReasonMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ItemRejectionReasonMaster\Database\Factories\ItemRejectionReasonMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRejectionReasonMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'item_rejection_reasons';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ItemRejectionReasonMasterFactory
    {
        return ItemRejectionReasonMasterFactory::new();
    }
}
