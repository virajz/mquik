<?php

namespace App\Modules\InsuranceDeductionTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InsuranceDeductionTypeMaster\Database\Factories\InsuranceDeductionTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceDeductionTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'insurance_deduction_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): InsuranceDeductionTypeMasterFactory
    {
        return InsuranceDeductionTypeMasterFactory::new();
    }
}
