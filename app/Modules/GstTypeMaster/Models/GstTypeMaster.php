<?php

namespace App\Modules\GstTypeMaster\Models;

use App\Concerns\Auditable;
use App\Modules\GstTypeMaster\Database\Factories\GstTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstTypeMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'gst_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): GstTypeMasterFactory
    {
        return GstTypeMasterFactory::new();
    }
}
