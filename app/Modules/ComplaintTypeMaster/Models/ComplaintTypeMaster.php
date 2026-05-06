<?php

namespace App\Modules\ComplaintTypeMaster\Models;

use App\Modules\ComplaintTypeMaster\Database\Factories\ComplaintTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintTypeMaster extends Model
{
    use HasFactory;

    protected $table = 'complaint_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ComplaintTypeMasterFactory
    {
        return ComplaintTypeMasterFactory::new();
    }
}
