<?php

namespace App\Modules\EmployeeCategoryMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeCategoryMaster\Database\Factories\EmployeeCategoryMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCategoryMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'employee_categories';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): EmployeeCategoryMasterFactory
    {
        return EmployeeCategoryMasterFactory::new();
    }
}
