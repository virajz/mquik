<?php

namespace App\Modules\EmployeeGradeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeGradeMaster\Database\Factories\EmployeeGradeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeGradeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'employee_grades';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): EmployeeGradeMasterFactory
    {
        return EmployeeGradeMasterFactory::new();
    }
}
