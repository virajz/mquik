<?php

namespace App\Modules\SalaryStructure\Models;

use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureLine extends Model
{
    protected $table = 'salary_structure_lines';

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponentMaster::class, 'salary_component_id');
    }
}
