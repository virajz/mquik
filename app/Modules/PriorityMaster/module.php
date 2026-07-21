<?php

use App\Modules\PriorityMaster\Exporters\PriorityExporter;
use App\Modules\PriorityMaster\Importers\PriorityImporter;

return [
    'label' => 'Priorities',
    'description' => 'Shared urgency levels for appointments, inspection orders and internal part orders.',
    'group' => 'Workshop',
    'icon' => 'flag',
    'permissions' => [
        'priority_master.view',
        'priority_master.create',
        'priority_master.update',
        'priority_master.delete',
        'priority_master.export',
        'priority_master.import',
    ],
    'exportable' => PriorityExporter::class,
    'importable' => PriorityImporter::class,
];
