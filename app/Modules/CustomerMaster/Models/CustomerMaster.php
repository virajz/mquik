<?php

namespace App\Modules\CustomerMaster\Models;

use App\Modules\CustomerMaster\Database\Factories\CustomerMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMaster extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CustomerMasterFactory
    {
        return CustomerMasterFactory::new();
    }
}
