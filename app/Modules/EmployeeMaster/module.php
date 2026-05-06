<?php

use App\Modules\EmployeeMaster\Exporters\EmployeeExporter;
use App\Modules\EmployeeMaster\Importers\EmployeeImporter;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;

return [
    'label' => 'Employees',
    'description' => 'Workshop staff — advisors, technicians, cashiers, accountants.',
    'group' => 'HR',
    'icon' => 'user-group',
    'permissions' => [
        'employee_master.view',
        'employee_master.create',
        'employee_master.update',
        'employee_master.delete',
        'employee_master.export',
        'employee_master.import',
    ],
    'exportable' => EmployeeExporter::class,
    'importable' => EmployeeImporter::class,
    'searchable' => [
        'model' => EmployeeMaster::class,
        'route' => 'employee-master.index',
    ],
];
