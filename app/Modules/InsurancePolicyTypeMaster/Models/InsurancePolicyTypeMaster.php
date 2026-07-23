<?php

namespace App\Modules\InsurancePolicyTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InsurancePolicyTypeMaster\Database\Factories\InsurancePolicyTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePolicyTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'insurance_policy_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'default_pass_percent' => 'decimal:2',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): InsurancePolicyTypeMasterFactory
    {
        return InsurancePolicyTypeMasterFactory::new();
    }
}
