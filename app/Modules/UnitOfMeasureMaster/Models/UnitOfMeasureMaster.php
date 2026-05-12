<?php

namespace App\Modules\UnitOfMeasureMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\UnitOfMeasureMaster\Database\Factories\UnitOfMeasureMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasureMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'units_of_measure';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): UnitOfMeasureMasterFactory
    {
        return UnitOfMeasureMasterFactory::new();
    }
}
