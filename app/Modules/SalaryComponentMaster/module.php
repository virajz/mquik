<?php

use App\Modules\SalaryComponentMaster\Exporters\SalaryComponentExporter;
use App\Modules\SalaryComponentMaster\Importers\SalaryComponentImporter;

return [
    'label' => 'Salary Components',
    'description' => 'Salary earnings & deductions - basic, HRA, PF, ESI, TDS etc.',
    'group' => 'HR',
    'icon' => 'squares-2x2',
    'permissions' => [
        'salary_component_master.view',
        'salary_component_master.create',
        'salary_component_master.update',
        'salary_component_master.delete',
        'salary_component_master.export',
        'salary_component_master.import',
    ],
    'exportable' => SalaryComponentExporter::class,
    'importable' => SalaryComponentImporter::class,
];
