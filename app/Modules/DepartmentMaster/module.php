<?php

use App\Modules\DepartmentMaster\Exporters\DepartmentExporter;
use App\Modules\DepartmentMaster\Importers\DepartmentImporter;

return [
    'label' => 'Departments',
    'description' => 'HR departments employees can be assigned to.',
    'group' => 'HR',
    'icon' => 'building-office-2',
    'permissions' => [
        'department_master.view',
        'department_master.create',
        'department_master.update',
        'department_master.delete',
        'department_master.export',
        'department_master.import',
    ],
    'exportable' => DepartmentExporter::class,
    'importable' => DepartmentImporter::class,
];
