<?php

namespace App\Modules\InsuranceCompanyMaster\Models;

use App\Modules\InsuranceCompanyMaster\Database\Factories\InsuranceCompanyMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompanyMaster extends Model
{
    use HasFactory;

    protected $table = 'insurance_companies';

    protected $guarded = [];

    protected $casts = [
        'default_pass_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): InsuranceCompanyMasterFactory
    {
        return InsuranceCompanyMasterFactory::new();
    }
}
