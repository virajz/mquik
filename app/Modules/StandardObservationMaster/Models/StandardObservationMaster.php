<?php

namespace App\Modules\StandardObservationMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\StandardObservationMaster\Database\Factories\StandardObservationMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandardObservationMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'standard_observations';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): StandardObservationMasterFactory
    {
        return StandardObservationMasterFactory::new();
    }
}
