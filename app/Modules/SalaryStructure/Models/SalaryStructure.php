<?php

namespace App\Modules\SalaryStructure\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalaryStructure\Database\Factories\SalaryStructureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'salary_structures';

    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'basic_salary' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    protected static array $searchableFields = ['notes'];

    protected static function newFactory(): SalaryStructureFactory
    {
        return SalaryStructureFactory::new();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalaryStructureLine::class, 'salary_structure_id')->orderBy('sequence_no');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'superseded' => 'Superseded',
        ];
    }
}
