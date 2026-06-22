<?php

use App\Modules\TechnicianFinding\Models\TechnicianFinding;

return [
    'label' => 'Technician Findings',
    'description' => 'Additional work a technician discovers mid-job — extra spares/labour, routed for approval.',
    'group' => 'Inspection',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'technician_finding.view',
        'technician_finding.create',
        'technician_finding.update',
        'technician_finding.delete',
    ],
    'searchable' => [
        'model' => TechnicianFinding::class,
        'route' => 'technician-finding.index',
    ],
];
