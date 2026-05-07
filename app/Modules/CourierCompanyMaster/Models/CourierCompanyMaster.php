<?php

namespace App\Modules\CourierCompanyMaster\Models;

use App\Concerns\Auditable;
use App\Modules\CourierCompanyMaster\Database\Factories\CourierCompanyMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierCompanyMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'courier_companies';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CourierCompanyMasterFactory
    {
        return CourierCompanyMasterFactory::new();
    }
}
