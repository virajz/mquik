<?php

namespace App\Modules\DesignationMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DesignationMaster\Database\Factories\DesignationMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignationMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'designations';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): DesignationMasterFactory
    {
        return DesignationMasterFactory::new();
    }
}
