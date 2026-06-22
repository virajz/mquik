<?php

namespace App\Modules\BayMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BayMaster\Database\Factories\BayMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BayMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'bays';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): BayMasterFactory
    {
        return BayMasterFactory::new();
    }
}
