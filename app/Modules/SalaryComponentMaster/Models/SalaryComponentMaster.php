<?php

namespace App\Modules\SalaryComponentMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\SalaryComponentMaster\Database\Factories\SalaryComponentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponentMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'salary_components';

    protected $guarded = [];

    protected $casts = [
        'default_value' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): SalaryComponentMasterFactory
    {
        return SalaryComponentMasterFactory::new();
    }

    /** @return array<string, string> */
    public static function componentTypes(): array
    {
        return [
            'earning' => 'Earning',
            'deduction' => 'Deduction',
        ];
    }

    /** @return array<string, string> */
    public static function calcMethods(): array
    {
        return [
            'fixed' => 'Fixed Amount',
            'percent_of_basic' => '% of Basic',
        ];
    }
}
