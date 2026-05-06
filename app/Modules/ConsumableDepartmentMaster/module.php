<?php

use App\Modules\ConsumableDepartmentMaster\Exporters\ConsumableDepartmentExporter;
use App\Modules\ConsumableDepartmentMaster\Importers\ConsumableDepartmentImporter;

return [
    'label' => 'Consumable Departments',
    'description' => 'Departments that consume workshop consumables — used to allocate cost to the right cost-centre.',
    'group' => 'Workshop',
    'icon' => 'beaker',
    'permissions' => [
        'consumable_department_master.view',
        'consumable_department_master.create',
        'consumable_department_master.update',
        'consumable_department_master.delete',
        'consumable_department_master.export',
        'consumable_department_master.import',
    ],
    'exportable' => ConsumableDepartmentExporter::class,
    'importable' => ConsumableDepartmentImporter::class,
];
