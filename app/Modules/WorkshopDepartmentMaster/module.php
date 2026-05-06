<?php

use App\Modules\WorkshopDepartmentMaster\Exporters\WorkshopDepartmentExporter;
use App\Modules\WorkshopDepartmentMaster\Importers\WorkshopDepartmentImporter;

return [
    'label' => 'Workshop Departments',
    'description' => 'Operational workshop bays/lines used across jobs and stocks.',
    'group' => 'Workshop',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'workshop_department_master.view',
        'workshop_department_master.create',
        'workshop_department_master.update',
        'workshop_department_master.delete',
        'workshop_department_master.export',
        'workshop_department_master.import',
    ],
    'exportable' => WorkshopDepartmentExporter::class,
    'importable' => WorkshopDepartmentImporter::class,
];
