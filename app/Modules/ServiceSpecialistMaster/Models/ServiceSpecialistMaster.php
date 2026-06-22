<?php

namespace App\Modules\ServiceSpecialistMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ServiceSpecialistMaster\Database\Factories\ServiceSpecialistMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSpecialistMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'service_specialists';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): ServiceSpecialistMasterFactory
    {
        return ServiceSpecialistMasterFactory::new();
    }
}
