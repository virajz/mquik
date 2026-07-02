<?php

use App\Modules\SalaryStructure\Models\SalaryStructure;

return [
    'label' => 'Salary Structures',
    'description' => 'Per-employee salary structure — basic + earning / deduction components with computed gross, deductions and net.',
    'group' => 'HR',
    'icon' => 'calculator',
    'permissions' => [
        'salary_structure.view',
        'salary_structure.create',
        'salary_structure.update',
        'salary_structure.delete',
    ],
    'searchable' => [
        'model' => SalaryStructure::class,
        'route' => 'salary-structure.index',
    ],
];
