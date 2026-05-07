<?php

namespace App\Modules\EmployeeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Database\Factories\EmployeeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'employees';

    protected static array $searchableFields = ['name', 'employee_code', 'phone', 'email'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->employee_code.($this->phone ? ' • +91 '.$this->phone : ''),
        ];
    }

    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'exit_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(DepartmentMaster::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(DesignationMaster::class, 'designation_id');
    }

    protected static function newFactory(): EmployeeMasterFactory
    {
        return EmployeeMasterFactory::new();
    }
}
