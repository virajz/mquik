<?php

namespace App\Modules\VendorTypeMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VendorTypeMaster\Database\Factories\VendorTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorTypeMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vendor_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VendorTypeMasterFactory
    {
        return VendorTypeMasterFactory::new();
    }
}
