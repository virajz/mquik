<?php

namespace App\Modules\RackMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\RackMaster\Database\Factories\RackMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RackMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'racks';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): RackMasterFactory
    {
        return RackMasterFactory::new();
    }
}
