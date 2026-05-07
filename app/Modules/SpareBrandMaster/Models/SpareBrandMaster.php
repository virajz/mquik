<?php

namespace App\Modules\SpareBrandMaster\Models;

use App\Concerns\Auditable;
use App\Modules\SpareBrandMaster\Database\Factories\SpareBrandMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpareBrandMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'spare_brands';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SpareBrandMasterFactory
    {
        return SpareBrandMasterFactory::new();
    }
}
