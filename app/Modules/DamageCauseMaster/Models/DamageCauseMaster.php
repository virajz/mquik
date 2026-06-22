<?php

namespace App\Modules\DamageCauseMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DamageCauseMaster\Database\Factories\DamageCauseMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageCauseMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'damage_causes';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): DamageCauseMasterFactory
    {
        return DamageCauseMasterFactory::new();
    }
}
