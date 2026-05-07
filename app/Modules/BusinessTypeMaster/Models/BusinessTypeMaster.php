<?php

namespace App\Modules\BusinessTypeMaster\Models;

use App\Concerns\Auditable;
use App\Modules\BusinessTypeMaster\Database\Factories\BusinessTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTypeMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'business_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): BusinessTypeMasterFactory
    {
        return BusinessTypeMasterFactory::new();
    }
}
