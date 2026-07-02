<?php

use App\Modules\EmployeeCategoryMaster\Exporters\EmployeeCategoryExporter;
use App\Modules\EmployeeCategoryMaster\Importers\EmployeeCategoryImporter;

return [
    'label' => 'Employee Categories',
    'description' => 'Employment categories for staff - permanent, probation, apprentice.',
    'group' => 'HR',
    'icon' => 'identification',
    'permissions' => [
        'employee_category_master.view',
        'employee_category_master.create',
        'employee_category_master.update',
        'employee_category_master.delete',
        'employee_category_master.export',
        'employee_category_master.import',
    ],
    'exportable' => EmployeeCategoryExporter::class,
    'importable' => EmployeeCategoryImporter::class,
];
