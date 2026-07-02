<?php

namespace App\Modules\ConsumableCategoryMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ConsumableCategoryMaster\Database\Factories\ConsumableCategoryMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumableCategoryMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'consumable_categories';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ConsumableCategoryMasterFactory
    {
        return ConsumableCategoryMasterFactory::new();
    }
}
