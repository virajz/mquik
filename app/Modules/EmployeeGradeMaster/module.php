<?php

use App\Modules\EmployeeGradeMaster\Exporters\EmployeeGradeExporter;
use App\Modules\EmployeeGradeMaster\Importers\EmployeeGradeImporter;

return [
    'label' => 'Employee Grades',
    'description' => 'Staff pay / seniority grades.',
    'group' => 'HR',
    'icon' => 'academic-cap',
    'permissions' => [
        'employee_grade_master.view',
        'employee_grade_master.create',
        'employee_grade_master.update',
        'employee_grade_master.delete',
        'employee_grade_master.export',
        'employee_grade_master.import',
    ],
    'exportable' => EmployeeGradeExporter::class,
    'importable' => EmployeeGradeImporter::class,
];
