<?php

namespace App\Modules\GateMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\GateMaster\Database\Factories\GateMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'gates';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): GateMasterFactory
    {
        return GateMasterFactory::new();
    }
}
